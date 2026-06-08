<?php

namespace App\Services\Payment\Adapters;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalAdapter implements PaymentGatewayInterface
{
    public function createPayment(PaymentGateway $gateway, array $payload): PaymentResult
    {
        try {
            $provider = $this->configureProvider($gateway);
            $provider->getAccessToken();

            $order = $provider->createOrder([
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => strtoupper($payload['currency'] ?? 'USD'),
                        'value' => (string) $payload['amount'],
                    ],
                    'description' => $payload['description'] ?? $payload['plan_name'] ?? 'Order',
                ]],
                'application_context' => [
                    'return_url' => $payload['success_url'],
                    'cancel_url' => $payload['cancel_url'],
                ],
            ]);

            $orderId = $order['id'] ?? null;
            if (!$orderId) {
                return PaymentResult::failed('Failed to create PayPal order', $order);
            }

            $redirectUrl = null;
            foreach ($order['links'] ?? [] as $link) {
                if ($link['rel'] === 'approve') {
                    $redirectUrl = $link['href'];
                    break;
                }
            }

            return PaymentResult::pending($orderId, $redirectUrl, $order);
        } catch (\Exception $e) {
            return PaymentResult::failed($e->getMessage());
        }
    }

    public function verifyPayment(PaymentGateway $gateway, Request $request): PaymentResult
    {
        try {
            $provider = $this->configureProvider($gateway);
            $provider->getAccessToken();

            $token = $request->get('token') ?? $request->input('token');
            if (!$token) {
                return PaymentResult::failed('Missing token parameter');
            }

            $response = $provider->capturePaymentOrder($token);

            if (($response['status'] ?? '') === 'COMPLETED') {
                $captureId = null;
                foreach ($response['purchase_units'] ?? [] as $unit) {
                    foreach ($unit['payments']['captures'] ?? [] as $capture) {
                        $captureId = $capture['id'];
                        break 2;
                    }
                }
                return PaymentResult::success($captureId ?? $response['id'], null, $response);
            }

            return PaymentResult::failed('PayPal payment not completed', $response);
        } catch (\Exception $e) {
            return PaymentResult::failed($e->getMessage());
        }
    }

    private function configureProvider(PaymentGateway $gateway): PayPalClient
    {
        $config = [
            'mode' => $gateway->is_test_mode ? 'sandbox' : 'live',
            'sandbox' => [
                'client_id' => $gateway->api_key,
                'client_secret' => $gateway->api_secret,
            ],
            'live' => [
                'client_id' => $gateway->api_key,
                'client_secret' => $gateway->api_secret,
            ],
            'payment_action' => 'Sale',
            'currency' => 'USD',
            'notify_url' => '',
            'locale' => 'en_US',
            'validate_ssl' => true,
        ];

        config(['paypal' => $config]);

        return new PayPalClient(config('paypal'));
    }

    public static function configKeys(): array
    {
        return [
            'api_key' => ['label' => 'Client ID', 'type' => 'text', 'required' => true],
            'api_secret' => ['label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ];
    }

    public static function extraConfigKeys(): array
    {
        return [];
    }
}
