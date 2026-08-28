<?php

namespace Tests\Tenancy;

use App\Exceptions\InvalidSubdomainException;
use App\Models\Clinic;
use App\Models\MarketingLead;
use App\Rules\UsableClinicSubdomain;
use App\Tenancy\Subdomain;
use Illuminate\Support\Facades\Validator;

/**
 * ARCHITECTURE §3.3 / §8.5 — the reserved-subdomain blocklist.
 *
 * `config('tenancy.reserved_subdomains')` shipped with ten labels in it and was
 * read by nothing whatsoever. Before this, `Clinic::create(['subdomain' =>
 * 'api'])` provisioned a clinic at api.<root>, database and all, with `api`
 * sitting in that list the entire time — verified by running it.
 *
 * §3.3 asks for the check in sign-up validation AND at provisioning, and says
 * plainly that the second is what protects the system "since the first can be
 * bypassed". So the tests that matter here are the model ones: they go through
 * Clinic::create() directly, exactly as a seeder, a console session or a future
 * tenant:create would, with no form in sight.
 */
class ReservedSubdomainTest extends TenancyTestCase
{
    // ── The layer that cannot be bypassed ──────────────────────────────────

    /**
     * Every configured label, not a sample. A list enforced for `api` but not
     * `blog` is not an enforced list.
     */
    public function test_no_clinic_can_be_provisioned_on_any_reserved_subdomain(): void
    {
        $reserved = Subdomain::reserved();

        $this->assertNotEmpty($reserved, 'The blocklist must not be empty, or this test proves nothing.');

        foreach ($reserved as $label) {
            try {
                Clinic::create([
                    'name' => 'Should Not Exist',
                    'subdomain' => $label,
                    'plan' => 'clinic',
                    'status' => 'active',
                    'owner_name' => 'Owner',
                    'owner_email' => "owner@{$label}.test",
                ]);

                $this->fail("A clinic was provisioned on the reserved subdomain [{$label}].");
            } catch (InvalidSubdomainException) {
                // expected
            }

            $this->assertSame(
                0,
                Clinic::where('subdomain', $label)->count(),
                "A registry row survived for the reserved subdomain [{$label}].",
            );
        }
    }

    public function test_renaming_an_existing_clinic_onto_a_reserved_subdomain_is_refused(): void
    {
        $clinic = $this->provisionClinic('alpha');

        $this->expectException(InvalidSubdomainException::class);

        try {
            $clinic->update(['subdomain' => 'api']);
        } finally {
            /*
             * The guard is on `saving`, not `creating`, and this is why: a
             * clinic that already has staff, records and a bookmarked address
             * is a worse place to discover the mistake than an empty new one.
             */
            $this->assertSame(
                'alpha',
                $clinic->fresh()->subdomain,
                'The stored subdomain must be unchanged after a refused rename.',
            );
        }
    }

    public function test_malformed_subdomains_are_refused(): void
    {
        $refused = 0;

        $cases = [
            'a' => 'a single character',
            '-lead' => 'a leading hyphen',
            'trail-' => 'a trailing hyphen',
            'has space' => 'a space',
            'UPPER' => 'uppercase letters',
            'under_score' => 'an underscore',
            'dot.ted' => 'a dot, which would be a second label',
            '' => 'an empty string',
            'x'.str_repeat('y', 60) => 'more than 40 characters',
        ];

        $expected = count($cases);

        foreach ($cases as $subdomain => $why) {
            try {
                Clinic::create([
                    'name' => 'Malformed',
                    'subdomain' => $subdomain,
                    'plan' => 'clinic',
                    'status' => 'active',
                    'owner_name' => 'Owner',
                    'owner_email' => 'owner@malformed.test',
                ]);

                $this->fail("A clinic was provisioned with {$why}: [{$subdomain}].");
            } catch (InvalidSubdomainException) {
                $refused++;
            }

            // Asserted per case, not just counted at the end, so a failure
            // names the input that got through.
            $this->assertSame(
                0,
                Clinic::where('subdomain', $subdomain)->count(),
                "A registry row survived for a subdomain with {$why}: [{$subdomain}].",
            );
        }

        $this->assertSame(
            $expected,
            $refused,
            'Every malformed subdomain must be refused by the model guard.',
        );
    }

    /**
     * The control. Without it, a guard that refused EVERYTHING would pass every
     * assertion above and nobody could ever sign up.
     */
    public function test_an_ordinary_clinic_subdomain_is_still_accepted(): void
    {
        $clinic = $this->provisionClinic('grace-medical');

        $this->assertSame('grace-medical', $clinic->fresh()->subdomain);
        $this->assertTrue(
            $clinic->database()->manager()->databaseExists($clinic->database()->getName()),
            'A legitimate clinic must still be fully provisioned.',
        );
    }

    // ── Derived reservations ───────────────────────────────────────────────

    public function test_labels_in_front_of_a_central_domain_are_reserved_too(): void
    {
        config([
            'tenancy.root_domain' => 'africhartemr.com',
            'tenancy.reserved_subdomains' => ['www'],
            'tenancy.central_domains' => [
                'africhartemr.com',
                'admin.africhartemr.com',
                'status.africhartemr.com',
                'unrelated.example.org',
                '127.0.0.1',
            ],
        ]);

        $this->assertTrue(Subdomain::isReserved('admin'), 'A central host label must be reserved.');
        $this->assertTrue(Subdomain::isReserved('status'), 'Adding a central host must reserve its label.');
        $this->assertTrue(Subdomain::isReserved('www'), 'The configured list still applies.');

        $this->assertFalse(
            Subdomain::isReserved('unrelated'),
            'A host that is not under our root domain must not reserve anything.',
        );
        $this->assertFalse(Subdomain::isReserved('grace'), 'Ordinary labels stay available.');
    }

    public function test_the_reserved_check_ignores_case_and_surrounding_space(): void
    {
        $this->assertTrue(Subdomain::isReserved('API'));
        $this->assertTrue(Subdomain::isReserved('  api  '));
    }

    // ── Sign-up validation ─────────────────────────────────────────────────

    public function test_signup_rejects_a_clinic_name_that_would_claim_a_reserved_address(): void
    {
        foreach (['Support', 'API', 'Blog', 'Help!', 'app', 'STATUS'] as $name) {
            $validator = Validator::make(
                ['clinic_name' => $name],
                ['clinic_name' => [new UsableClinicSubdomain]],
            );

            $this->assertTrue(
                $validator->fails(),
                "[{$name}] slugs to [".Subdomain::from($name).'], which is reserved, and must be refused.',
            );
        }
    }

    public function test_signup_accepts_ordinary_clinic_names(): void
    {
        foreach ([
            'Grace Medical Centre',
            'St. Mary\'s Clinic',
            'API Diagnostics Limited',      // slugs to api-diagnostics-limited, not api
            'Blogun Family Practice',       // must not trip on the substring "blog"
        ] as $name) {
            $validator = Validator::make(
                ['clinic_name' => $name],
                ['clinic_name' => [new UsableClinicSubdomain]],
            );

            $this->assertFalse(
                $validator->fails(),
                "[{$name}] is a legitimate clinic name and must be accepted (slug: ".Subdomain::from($name).').',
            );
        }
    }

    /**
     * The rule tests above prove the rule. This proves it is actually WIRED —
     * over HTTP, through the real route, middleware, form request and
     * controller — and that a rejected sign-up writes no lead.
     */
    public function test_the_signup_endpoint_refuses_a_reserved_clinic_name(): void
    {
        $central = 'http://'.config('tenancy.central_domains')[0];

        $payload = [
            'contact_name' => 'A Person',
            'email' => 'person@example.test',
            'phone' => '08031234567',
            'city' => 'Port Harcourt',
            'terms' => '1',
        ];

        $this->post($central.'/signup', $payload + ['clinic_name' => 'Support'])
            ->assertSessionHasErrors('clinic_name');

        $this->assertSame(
            0,
            MarketingLead::where('clinic_name', 'Support')->count(),
            'A refused sign-up must not leave a lead behind.',
        );

        // Control: the same request with an ordinary name goes through, so the
        // assertion above is about the name and not about a broken endpoint.
        $this->post($central.'/signup', $payload + ['clinic_name' => 'Grace Medical Centre'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            1,
            MarketingLead::where('clinic_name', 'Grace Medical Centre')->count(),
            'A legitimate sign-up must still be captured.',
        );
    }

    // ── The preview must not promise what the server will refuse ───────────

    /**
     * The Blade preview derives the address in JavaScript; the server derives it
     * in PHP. They cannot share an implementation, so this runs the REAL
     * JavaScript — lifted from the view, executed in node — against the real PHP
     * for the same inputs.
     *
     * Transcribing the JS into PHP and comparing that would only prove the
     * transcription matched itself.
     */
    public function test_the_javascript_preview_derives_the_same_address_as_php(): void
    {
        $view = file_get_contents(resource_path('views/marketing/signup.blade.php'));

        preg_match('/get slug\(\) \{(.*?)\n                                \},/s', $view, $matches);

        $this->assertNotEmpty($matches, 'Could not find the slug getter in signup.blade.php — has it been renamed?');

        // `this.clinic` in the view becomes the function's argument here.
        $body = str_replace('this.clinic', 'input', $matches[1]);

        $names = [
            'Grace Medical Centre',
            'St. Mary\'s Clinic',
            'API Diagnostics Limited',
            '  Leading and trailing  ',
            'Hyphen -- heavy -- name',
            'A very long clinic name that will certainly exceed the forty character limit',
            'Ikeja  Family   Practice',
            '123 Clinic',
        ];

        $script = 'const slug = (input) => {'.$body.'};'
            ."\nconsole.log(JSON.stringify(".json_encode($names).'.map(slug)));';

        $file = tempnam(sys_get_temp_dir(), 'slug').'.js';
        file_put_contents($file, $script);

        $output = shell_exec('node '.escapeshellarg($file).' 2>&1');
        @unlink($file);

        $fromJs = json_decode(trim((string) $output), true);

        $this->assertIsArray($fromJs, "The preview JavaScript did not run: {$output}");

        foreach ($names as $i => $name) {
            $this->assertSame(
                $fromJs[$i],
                Subdomain::from($name),
                "PHP and the on-screen preview disagree about [{$name}]. The visitor is being shown "
                .'an address the server would not give them.',
            );
        }
    }
}
