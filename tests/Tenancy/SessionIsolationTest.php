<?php

namespace Tests\Tenancy;

use App\Enums\StaffRole;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

/**
 * §6.2 — SESSION ISOLATION.
 *
 * > A session established on tenant A is not valid on tenant B.
 *
 * The leak this guards against is a single line of configuration. Set
 * SESSION_DOMAIN to ".africhartemr.com" and the session cookie is shared across
 * every clinic subdomain — one authenticated session presented to all of them.
 * For a system holding medical records that is about as bad as it gets, and
 * nothing about it looks broken from the inside.
 *
 * These tests do NOT use actingAs(). That would bypass the session mechanism
 * entirely and pass no matter how the cookie were scoped — a happy-path test
 * dressed as a security one. Every case here performs a real login POST and
 * replays the real cookie.
 */
class SessionIsolationTest extends TenancyTestCase
{
    public function test_session_domain_is_null_so_the_cookie_is_host_only(): void
    {
        /*
         * The guard that fails loudly. If someone sets SESSION_DOMAIN to share
         * a cookie across subdomains — for a "convenience" that would seem
         * reasonable in isolation — this test is what tells them what they have
         * actually done.
         */
        $this->assertNull(
            config('session.domain'),
            'SESSION_DOMAIN must stay unset. Scoping the cookie to the parent domain shares '
            .'one authenticated session across EVERY clinic subdomain.',
        );
    }

    public function test_a_session_cookie_from_one_clinic_is_not_authenticated_on_another(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->createStaff($a, 'doctor@alpha.test');
        $this->createStaff($b, 'doctor@bravo.test');

        // A real login on A — not actingAs, which would bypass the cookie
        // entirely and pass however the session were scoped.
        $login = $this->post("http://alpha.{$this->rootDomain()}/login", [
            'email' => 'doctor@alpha.test',
            'password' => 'password123',
        ]);

        $login->assertRedirect("http://alpha.{$this->rootDomain()}/dashboard");

        $cookies = $this->sessionCookiesFrom($login);
        $this->assertNotEmpty($cookies, 'Login must have set a session cookie to replay.');

        /*
         * refreshApplication() before each request, and it is not incidental.
         *
         * Laravel's test client reuses ONE application container across every
         * request in a test method, so the resolved user and the in-memory
         * session survive between them. Without this, B returned 200 and
         * auth()->check() reported doctor@alpha.test — which looks exactly like
         * a cross-tenant breach and is not one: B's sessions table had ZERO
         * rows, so B never read a session at all. The auth state had simply
         * never left the container.
         *
         * Rebuilding forces each request to rely only on the cookie, which is
         * what separate browser requests do in production. The control below
         * is what proves this is a faithful harness rather than a broken one:
         * A must still authenticate. If refreshing had merely destroyed the
         * session, A would fail too and the "isolation" would be meaningless.
         */
        $this->refreshApplication();
        $onA = $this->withUnencryptedCookies($cookies)
            ->get("http://alpha.{$this->rootDomain()}/dashboard");

        $onA->assertOk();   // CONTROL: the session genuinely works where it was issued.

        // THE LEAK ATTEMPT: the same cookie, presented to a different clinic.
        $this->refreshApplication();
        $onB = $this->withUnencryptedCookies($cookies)
            ->get("http://bravo.{$this->rootDomain()}/dashboard");

        $onB->assertRedirect("http://bravo.{$this->rootDomain()}/login");
    }

    public function test_the_session_row_lives_in_the_issuing_clinics_database_only(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->createStaff($a, 'doctor@alpha.test');

        $this->post("http://alpha.{$this->rootDomain()}/login", [
            'email' => 'doctor@alpha.test',
            'password' => 'password123',
        ])->assertRedirect();

        /*
         * The evidence, not the behaviour. Under D1 the session driver is
         * `database`, so isolation is a question of WHERE the row is — and a
         * row that only exists in A's schema cannot be found by a lookup
         * running against B's.
         */
        $inA = $this->inTenant($a, fn () => DB::table('sessions')->count());
        $inB = $this->inTenant($b, fn () => DB::table('sessions')->count());

        $this->assertGreaterThan(0, $inA, "A's sessions table must hold the session it issued.");
        $this->assertSame(0, $inB, "B's sessions table must be empty — the session is not shared.");
    }

    public function test_logging_into_both_clinics_keeps_two_separate_sessions(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->createStaff($a, 'doctor@alpha.test');
        $this->createStaff($b, 'doctor@bravo.test');

        $this->post("http://alpha.{$this->rootDomain()}/login", [
            'email' => 'doctor@alpha.test', 'password' => 'password123',
        ])->assertRedirect();

        // A different browser, not the same one navigating elsewhere.
        $this->refreshApplication();

        $this->post("http://bravo.{$this->rootDomain()}/login", [
            'email' => 'doctor@bravo.test', 'password' => 'password123',
        ])->assertRedirect();

        // One session each, in its own database — never two in one, or one shared.
        $inA = $this->inTenant($a, fn () => DB::table('sessions')->count());
        $inB = $this->inTenant($b, fn () => DB::table('sessions')->count());

        $this->assertSame(1, $inA, "A must hold exactly its own session.");
        $this->assertSame(1, $inB, "B must hold exactly its own session.");
    }

    public function test_a_staff_member_of_one_clinic_cannot_log_in_at_another(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->createStaff($a, 'doctor@alpha.test');

        /*
         * A's credentials, presented at B's login. B's staff table has no such
         * row, so this must fail as an ordinary bad login — which is also the
         * correct message: it must not reveal that the account exists elsewhere.
         */
        $this->post("http://bravo.{$this->rootDomain()}/login", [
            'email' => 'doctor@alpha.test',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_session_id_issued_by_one_clinic_does_not_exist_in_the_others_store(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->createStaff($a, 'doctor@alpha.test');

        $this->post("http://alpha.{$this->rootDomain()}/login", [
            'email' => 'doctor@alpha.test',
            'password' => 'password123',
        ])->assertRedirect();

        /*
         * Driver-level evidence, independent of any HTTP harness quirk.
         *
         * Under D1 the session driver is `database`, so "is this session valid
         * here?" reduces to "does this row exist in THIS database?". Take the
         * id A actually issued and look for it in B.
         */
        $sessionId = $this->inTenant($a, fn () => DB::table('sessions')->value('id'));

        $this->assertNotNull($sessionId, "A must have stored the session it issued.");

        $foundInA = $this->inTenant($a, fn () => DB::table('sessions')->where('id', $sessionId)->exists());
        $foundInB = $this->inTenant($b, fn () => DB::table('sessions')->where('id', $sessionId)->exists());

        $this->assertTrue($foundInA, "The session must exist in the clinic that issued it.");
        $this->assertFalse($foundInB, "LEAK: A's session id [{$sessionId}] is resolvable inside B.");
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function rootDomain(): string
    {
        return config('tenancy.root_domain');
    }

    private function createStaff($clinic, string $email): void
    {
        $this->inTenant($clinic, function () use ($email) {
            $staff = Staff::create([
                'name' => 'Test Doctor',
                'email' => $email,
                'password' => 'password123',
                'role' => StaffRole::Admin,
            ]);

            // Not fillable, and `verified` middleware gates the dashboard.
            $staff->forceFill(['email_verified_at' => now()])->save();
        });
    }

    /** @return array<string, string> */
    private function sessionCookiesFrom($response): array
    {
        $name = config('session.cookie');
        $cookies = [];

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
        }

        return $cookies;
    }
}
