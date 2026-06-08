<?php

namespace App\Services\Payment;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,          // succeeded, pending, failed, requires_action
        public readonly ?string $gatewayOrderId = null,
        public readonly ?string $receiptUrl = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = [],
    ) {}

    public static function success(string $gatewayOrderId, ?string $receiptUrl = null, array $raw = []): self
    {
        return new self(true, 'succeeded', $gatewayOrderId, $receiptUrl, null, null, $raw);
    }

    public static function pending(string $gatewayOrderId, ?string $redirectUrl = null, array $raw = []): self
    {
        return new self(true, 'pending', $gatewayOrderId, null, $redirectUrl, null, $raw);
    }

    public static function requiresAction(string $gatewayOrderId, ?string $redirectUrl = null, array $raw = []): self
    {
        return new self(true, 'requires_action', $gatewayOrderId, null, $redirectUrl, null, $raw);
    }

    public static function failed(string $error, array $raw = []): self
    {
        return new self(false, 'failed', null, null, null, $error, $raw);
    }
}
