<?php

namespace App\Services\Payment\Adapters;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RedirectAdapter implements PaymentGatewayInterface
{
    public function createPayment(PaymentGateway $gateway, array $payload): PaymentResult
    {
        try {
            $orderId = $payload['order_id'] ?? 'ORDER-' . strtoupper(Str::random(12));
            $amount = $payload['amount'];
            $currency = $payload['currency'] ?? 'USD';
            $extraConfig = $gateway->extra_config ?? [];

            $requestData = [
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
                'customer_name' => $payload['customer_name'] ?? '',
                'customer_email' => $payload['customer_email'] ?? '',
                'customer_phone' => $payload['customer_phone'] ?? '',
                'description' => $payload['description'] ?? $payload['plan_name'] ?? 'Order',
                'success_url' => $payload['success_url'],
                'cancel_url' => $payload['cancel_url'],
                'callback_url' => $payload['callback_url'] ?? $payload['success_url'],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $gateway->api_secret,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post(rtrim($gateway->base_url, '/') . ($extraConfig['create_endpoint'] ?? '/v1/transactions'), $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $redirectUrl = $data['redirect_url'] ?? $data['payment_url'] ?? $data['url'] ?? null;

                if (!$redirectUrl && isset($data['token'])) {
                    $redirectUrl = rtrim($gateway->base_url, '/') . ($extraConfig['redirect_path'] ?? '/payment/') . $data['token'];
                }

                $gatewayOrderId = $data['order_id'] ?? $data['transaction_id'] ?? $data['id'] ?? $orderId;

                return PaymentResult::pending($gatewayOrderId, $redirectUrl, $data);
            }

            return PaymentResult::failed(
                $response->json('message') ?? $response->body() ?? 'Gateway request failed',
                $response->json() ?? []
            );
        } catch (\Exception $e) {
            return PaymentResult::failed($e->getMessage());
        }
    }

    public function verifyPayment(PaymentGateway $gateway, Request $request): PaymentResult
    {
        try {
            $extraConfig = $gateway->extra_config ?? [];
            $transactionId = $request->input('transaction_id')
                ?? $request->input('order_id')
                ?? $request->input('id');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $gateway->api_secret,
                'Accept' => 'application/json',
            ])
                ->timeout(30)
                ->get(rtrim($gateway->base_url, '/') . ($extraConfig['status_endpoint'] ?? '/v1/transactions/') . $transactionId);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['status'] ?? $data['transaction_status'] ?? $data['payment_status'] ?? '';

                $successStatuses = $extraConfig['success_statuses'] ?? ['settlement', 'success', 'capture', 'completed', 'paid'];

                if (in_array(strtolower($status), array_map('strtolower', $successStatuses))) {
                    return PaymentResult::success(
                        $data['transaction_id'] ?? $data['order_id'] ?? $transactionId,
                        $data['receipt_url'] ?? $data['receipt'] ?? null,
                        $data
                    );
                }

                return PaymentResult::failed("Payment status: {$status}", $data);
            }

            return PaymentResult::failed($response->json('message') ?? 'Verification failed', $response->json() ?? []);
        } catch (\Exception $e) {
            return PaymentResult::failed($e->getMessage());
        }
    }

    public static function configKeys(): array
    {
        return [
            'api_key' => ['label' => 'Merchant ID / API Key', 'type' => 'text', 'required' => false],
            'api_secret' => ['label' => 'Server Key / Secret', 'type' => 'password', 'required' => true],
        ];
    }

    public static function extraConfigKeys(): array
    {
        return [
            'create_endpoint' => ['label' => 'Create Transaction Endpoint', 'type' => 'text', 'default' => '/v1/transactions', 'placeholder' => 'e.g. /snap/v1/transactions'],
            'status_endpoint' => ['label' => 'Status Check Endpoint', 'type' => 'text', 'default' => '/v1/transactions/', 'placeholder' => 'e.g. /v2/{id}/status'],
            'redirect_path' => ['label' => 'Redirect Path (optional)', 'type' => 'text', 'placeholder' => 'e.g. /snap/v2/vtweb/'],
            'success_statuses' => ['label' => 'Success Statuses (comma-separated)', 'type' => 'text', 'default' => 'settlement,success,capture,completed,paid'],
            'signature_key' => ['label' => 'Signature / HMAC Key (optional)', 'type' => 'password', 'placeholder' => 'For callback verification'],
        ];
    }
}
