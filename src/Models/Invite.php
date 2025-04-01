<?php

namespace RepositorioMaster\TranslationsUI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use RepositorioMaster\TranslationsUI\Enums\RoleEnum;
use RepositorioMaster\TranslationsUI\Traits\HasDatabaseConnection;

class Invite extends Authenticatable
{
    use HasDatabaseConnection;
    use HasFactory;
    use Notifiable;

    protected $guarded = [];

    protected $table = 'ltu_invites';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'role' => RoleEnum::class,
        'languages' => 'json',
    ];
}
