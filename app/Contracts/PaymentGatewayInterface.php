<?php

namespace App\Contracts;

use App\Models\PaymentGateway;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Create a payment session/order with the gateway.
     */
    public function createPayment(PaymentGateway $gateway, array $payload): PaymentResult;

    /**
     * Verify / check payment status from gateway callback.
     */
    public function verifyPayment(PaymentGateway $gateway, Request $request): PaymentResult;

    /**
     * Get gateway configuration keys required for setup.
     */
    public static function configKeys(): array;

    /**
     * Get extra config keys for this format (optional fields).
     */
    public static function extraConfigKeys(): array;
}
