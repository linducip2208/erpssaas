<?php

namespace App\Services\Payment\Adapters;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RestApiAdapter implements PaymentGatewayInterface
{
    public function createPayment(PaymentGateway $gateway, array $payload): PaymentResult
    {
        try {
            $orderId = $payload['order_id'] ?? 'ORDER-' . strtoupper(Str::random(12));
            $extraConfig = $gateway->extra_config ?? [];

            $requestData = [
                $extraConfig['id_field'] ?? 'reference' => $orderId,
                $extraConfig['amount_field'] ?? 'amount' => (int) round($payload['amount'] * 100),
                'currency' => strtoupper($payload['currency'] ?? 'USD'),
                'email' => $payload['customer_email'] ?? '',
                'callback_url' => $payload['callback_url'] ?? $payload['success_url'],
                'metadata' => $payload['metadata'] ?? [],
            ];

            $headers = $this->buildHeaders($gateway);
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post(rtrim($gateway->base_url, '/') . ($extraConfig['create_endpoint'] ?? '/transaction/initialize'), $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['status'] ?? '';

                $redirectUrl = $data['data']['authorization_url'] ?? $data['authorization_url'] ?? $data['redirect_url'] ?? null;
                $gatewayOrderId = $data['data']['reference'] ?? $data['data']['id'] ?? $data['reference'] ?? $orderId;

                if ($redirectUrl) {
                    return PaymentResult::pending($gatewayOrderId, $redirectUrl, $data);
                }

                if (in_array(strtolower($status), ['success', 'true', '1'])) {
                    return PaymentResult::success($gatewayOrderId, null, $data);
                }

                return PaymentResult::failed($data['message'] ?? 'Unknown status', $data);
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
            $reference = $request->input('reference')
                ?? $request->input('transaction_id')
                ?? $request->input('id');

            $headers = $this->buildHeaders($gateway);
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get(rtrim($gateway->base_url, '/') . ($extraConfig['verify_endpoint'] ?? '/transaction/verify/') . $reference);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['data']['status'] ?? $data['status'] ?? '';

                $successStatuses = $extraConfig['success_statuses'] ?? ['success', 'successful', 'completed', 'paid', 'settlement'];

                if (in_array(strtolower($status), array_map('strtolower', $successStatuses))) {
                    return PaymentResult::success(
                        $data['data']['reference'] ?? $data['data']['id'] ?? $reference,
                        $data['data']['receipt_url'] ?? null,
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

    private function buildHeaders(PaymentGateway $gateway): array
    {
        $extraConfig = $gateway->extra_config ?? [];
        $authType = $extraConfig['auth_type'] ?? 'bearer';

        return match ($authType) {
            'basic' => [
                'Authorization' => 'Basic ' . base64_encode($gateway->api_key . ':' . $gateway->api_secret),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'header_key' => [
                $extraConfig['api_key_header'] ?? 'X-API-Key' => $gateway->api_key,
                $extraConfig['api_secret_header'] ?? 'X-API-Secret' => $gateway->api_secret,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            default => [
                'Authorization' => 'Bearer ' . $gateway->api_secret,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        };
    }

    public static function configKeys(): array
    {
        return [
            'api_key' => ['label' => 'API Key / Public Key', 'type' => 'text', 'required' => true],
            'api_secret' => ['label' => 'Secret Key / Private Key', 'type' => 'password', 'required' => true],
        ];
    }

    public static function extraConfigKeys(): array
    {
        return [
            'auth_type' => ['label' => 'Auth Type', 'type' => 'select', 'options' => ['bearer' => 'Bearer Token', 'basic' => 'Basic Auth', 'header_key' => 'Custom Headers'], 'default' => 'bearer'],
            'api_key_header' => ['label' => 'API Key Header Name', 'type' => 'text', 'placeholder' => 'X-API-Key', 'show_if' => ['auth_type' => 'header_key']],
            'api_secret_header' => ['label' => 'API Secret Header Name', 'type' => 'text', 'placeholder' => 'X-API-Secret', 'show_if' => ['auth_type' => 'header_key']],
            'create_endpoint' => ['label' => 'Create Payment Endpoint', 'type' => 'text', 'default' => '/transaction/initialize', 'placeholder' => 'e.g. /transaction/initialize'],
            'verify_endpoint' => ['label' => 'Verify Payment Endpoint', 'type' => 'text', 'default' => '/transaction/verify/', 'placeholder' => 'e.g. /transaction/verify/'],
            'id_field' => ['label' => 'Order ID Field Name', 'type' => 'text', 'default' => 'reference'],
            'amount_field' => ['label' => 'Amount Field Name', 'type' => 'text', 'default' => 'amount'],
            'success_statuses' => ['label' => 'Success Statuses (comma-separated)', 'type' => 'text', 'default' => 'success,successful,completed,paid'],
        ];
    }
}
