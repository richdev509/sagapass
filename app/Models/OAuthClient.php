<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthClient extends Model
{
    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    protected $table = 'developer_applications';

    /**
     * Les attributs qui peuvent être assignés en masse.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'website',
        'logo_path',
        'client_id',
        'client_secret',
        'redirect_uris',
        'allowed_scopes',
        'status',
        'is_trusted',
        'approved_at',
        'approved_by',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'redirect_uris' => 'array',
        'allowed_scopes' => 'array',
        'is_trusted' => 'boolean',
        'approved_at' => 'datetime',
    ];
}
