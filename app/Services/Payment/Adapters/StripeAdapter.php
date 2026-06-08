<?php

namespace App\Services\Payment\Adapters;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;

class StripeAdapter implements PaymentGatewayInterface
{
    public function createPayment(PaymentGateway $gateway, array $payload): PaymentResult
    {
        try {
            \Stripe\Stripe::setApiKey($gateway->api_secret);

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($payload['currency'] ?? 'usd'),
                        'product_data' => ['name' => $payload['description'] ?? $payload['plan_name'] ?? 'Order'],
                        'unit_amount' => (int) round($payload['amount'] * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $payload['success_url'],
                'cancel_url' => $payload['cancel_url'],
                'metadata' => $payload['metadata'] ?? [],
            ]);

            return PaymentResult::pending($session->id, $session->url, $session->toArray());
        } catch (\Exception $e) {
            return PaymentResult::failed($e->getMessage());
        }
    }

    public function verifyPayment(PaymentGateway $gateway, Request $request): PaymentResult
    {
        try {
            \Stripe\Stripe::setApiKey($gateway->api_secret);

            $sessionId = $request->get('session_id') ?? $request->input('session_id');
            if (!$sessionId) {
                return PaymentResult::failed('Missing session_id parameter');
            }

            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $receiptUrl = null;
                if ($session->payment_intent) {
                    $intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
                    $receiptUrl = $intent->charges?->data[0]?->receipt_url ?? null;
                }
                return PaymentResult::success($session->payment_intent ?? $sessionId, $receiptUrl, $session->toArray());
            }

            return PaymentResult::failed('Payment not completed', $session->toArray());
        } catch (\Exception $e) {
            return PaymentResult::failed($e->getMessage());
        }
    }

    public static function configKeys(): array
    {
        return [
            'api_key' => ['label' => 'Publishable Key', 'type' => 'text', 'required' => true],
            'api_secret' => ['label' => 'Secret Key', 'type' => 'password', 'required' => true],
            'webhook_secret' => ['label' => 'Webhook Signing Secret', 'type' => 'password', 'required' => false],
        ];
    }

    public static function extraConfigKeys(): array
    {
        return [];
    }
}
