<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserAuthenticationObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy(UserAuthenticationObserver::class)]
final class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, \Illuminate\Database\Eloquent\SoftDeletes, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'school_id',
        'proved_id',
        'sous_division_id',
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

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function proved(): BelongsTo
    {
        return $this->belongsTo(Proved::class);
    }

    public function sousDivision(): BelongsTo
    {
        return $this->belongsTo(SousDivision::class);
    }

    public function personals(): HasMany
    {
        return $this->hasMany(AcademicPersonal::class);
    }

    public function academicPersonal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AcademicPersonal::class, 'user_id');
    }

    public function parentProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Parents::class, 'user_id');
    }

    /**
     * Les rôles Spatie sont stockés avec guard_name « web » (session + Sanctum sur ce même User).
     * Sans cela, hasRole() peut être faux sur les requêtes API et le middleware role:… renvoie 403.
     */
    public function guardName(): string
    {
        return 'web';
    }

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
            'last_login_at' => 'datetime',
            'school_id' => 'integer',
            'proved_id' => 'integer',
            'sous_division_id' => 'integer',
        ];
    }
}
