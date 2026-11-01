<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'google_id',
        'theme',
        'reduced_motion',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'reduced_motion' => 'boolean',
        ];
    }

    /**
     * Decorative 16-digit card number, unique per user. The id is mapped through a bijection
     * (odd multiplier not divisible by 5, modulo 10^14), so two users can never share a number.
     */
    public function cardNumber(): string
    {
        $body = '4'.str_pad((string) (((int) $this->id * 48271938473 + 31415926535) % 100000000000000), 14, '0', STR_PAD_LEFT);

        $sum = 0;
        foreach (array_reverse(str_split($body)) as $i => $digit) {
            $d = (int) $digit;
            if ($i % 2 === 0) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
        }

        return $body.((10 - $sum % 10) % 10);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function transations(): HasMany
    {
        return $this->transactions();
    }
}
