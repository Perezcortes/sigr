<?php

namespace App\Models;

use CWSPS154\UsersRolesPermissions\Models\HasRole;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Filament\Models\Contracts\HasAvatar;


class User extends Authenticatable implements HasMedia, HasAvatar, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRole, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'role_id',
        'last_seen',
        'is_active'
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
        ];
    }

    /**
     * URL de imagen por defecto para avatares
     */
    protected const DEFAULT_AVATAR_URL = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';

    /**
     * Obtener la URL del avatar del usuario
     * Si no hay avatar o hay un error, retorna una imagen por defecto
     */
    public function getFilamentAvatarUrl(): ?string
    {
        try {
            $avatarUrl = $this->getFirstMediaUrl('profile-images', 'avatar');
            
            // Si no hay avatar, usar imagen por defecto
            if (empty($avatarUrl)) {
                return self::DEFAULT_AVATAR_URL;
            }
            
            // Verificar si la URL es válida (no contiene localhost o errores)
            // Si contiene localhost, usar imagen por defecto para evitar problemas
            if (str_contains($avatarUrl, 'localhost') || str_contains($avatarUrl, '127.0.0.1')) {
                return self::DEFAULT_AVATAR_URL;
            }
            
            return $avatarUrl;
        } catch (\Exception $e) {
            // Si hay cualquier error, usar imagen por defecto
            return self::DEFAULT_AVATAR_URL;
        }
    }

    /**
     * Relación con Applications
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
