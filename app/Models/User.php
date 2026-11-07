<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

#[Fillable(['name', 'username', 'password', 'is_owner'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable;

    /**
     * The account a passkey is saved under on the phone: the username, as
     * there is no email.
     */
    public function getPasskeyUsername(): string
    {
        return $this->username;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_owner' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * Whether the owner has deactivated this user.
     */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * Stop this user from logging in. Their records stay linked to them.
     */
    public function deactivate(): void
    {
        $this->forceFill(['deactivated_at' => now()])->save();
    }

    /**
     * Let a deactivated user log in again.
     */
    public function activate(): void
    {
        $this->forceFill(['deactivated_at' => null])->save();
    }
}
