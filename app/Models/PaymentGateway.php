<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'format',
        'display_name',
        'description',
        'base_url',
        'api_key',
        'api_secret',
        'webhook_secret',
        'extra_config',
        'supported_currencies',
        'is_enabled',
        'is_test_mode',
        'logo',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'extra_config' => 'array',
        'supported_currencies' => 'array',
        'is_enabled' => 'boolean',
        'is_test_mode' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $hidden = ['api_key', 'api_secret', 'webhook_secret'];

    public function setApiKeyAttribute($value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiKeyAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function setApiSecretAttribute($value): void
    {
        $this->attributes['api_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiSecretAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function setWebhookSecretAttribute($value): void
    {
        $this->attributes['webhook_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getWebhookSecretAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeByFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    public function scopeForTenant($query)
    {
        $userId = creatorId();
        return $query->where(function ($q) use ($userId) {
            $q->where('created_by', $userId)
              ->orWhereNull('created_by');
        });
    }
}
