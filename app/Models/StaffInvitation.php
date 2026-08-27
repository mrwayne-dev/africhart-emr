<?php

namespace App\Models;

use App\Enums\StaffRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An invitation to join ONE clinic's staff. Lives in that clinic's database.
 *
 * The raw token exists only twice: in the email that carries it, and in memory
 * for the moment it takes to hash it. Nothing stores it and nothing can recover
 * it — an admin who loses the link issues a new invitation.
 */
class StaffInvitation extends Model
{
    protected $fillable = [
        'token_hash', 'email', 'name', 'role', 'invited_by', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => StaffRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** How long a new invitation stays valid. */
    public const EXPIRES_AFTER_DAYS = 7;

    /**
     * Issue an invitation, returning it with the ONE-TIME raw token attached.
     *
     * The token is returned rather than stored so the caller can put it in the
     * email and then let it fall out of scope. Read it from the return value
     * immediately; there is no second chance.
     *
     * @return array{0: self, 1: string} the invitation and its raw token
     */
    public static function issue(string $email, StaffRole $role, ?string $name, ?int $invitedBy): array
    {
        /*
         * Str::random is alphanumeric and cryptographically secure (it draws
         * from random_bytes), so the token is URL-safe without encoding. 64
         * characters puts guessing far out of reach of the route's throttle.
         */
        $token = Str::random(64);

        $invitation = self::create([
            'token_hash' => self::hash($token),
            'email' => $email,
            'name' => $name,
            'role' => $role,
            'invited_by' => $invitedBy,
            'expires_at' => now()->addDays(self::EXPIRES_AFTER_DAYS),
        ]);

        return [$invitation, $token];
    }

    /**
     * SHA-256, deliberately NOT a password hash.
     *
     * bcrypt/argon are slow BY DESIGN to make guessing a low-entropy human
     * password expensive. This token has 64 random alphanumeric characters —
     * roughly 380 bits — so there is nothing to slow down, and a slow hash
     * could not be used as a lookup key at all: every row would have its own
     * salt, forcing a full table scan and a verify() per row on every request.
     * Hashing to a deterministic value is what allows the indexed lookup that
     * makes this constant-time and enumeration-proof.
     */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Find a usable invitation for a raw token, or null.
     *
     * Null covers unknown, expired, already accepted and revoked alike — the
     * caller cannot tell which, and that is deliberate. Distinguishing them
     * would let someone probe which tokens exist.
     */
    public static function findPending(string $token): ?self
    {
        return self::query()->pending()->where('token_hash', self::hash($token))->first();
    }

    /** @param  Builder<self>  $query */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    public function markAccepted(Staff $staff): void
    {
        $this->forceFill([
            'accepted_at' => now(),
            'accepted_by_staff_id' => $staff->id,
        ])->save();
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'invited_by');
    }
}
