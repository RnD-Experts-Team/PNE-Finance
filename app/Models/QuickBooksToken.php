<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class QuickBooksToken extends Model
{
    use HasFactory;

    protected $table = 'quickbooks_tokens';

    protected $fillable = [
        'realm_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // Accessor to decrypt refresh_token when accessed
    public function getRefreshTokenAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    // Mutator to encrypt refresh_token before saving
    public function setRefreshTokenAttribute($value)
    {
        $this->attributes['refresh_token'] = Crypt::encryptString($value);
    }

    // Accessor to decrypt realm_id when accessed
    public function getRealmIdAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    // Mutator to encrypt realm_id before saving
    public function setRealmIdAttribute($value)
    {
        $this->attributes['realm_id'] = Crypt::encryptString($value);
    }
}
