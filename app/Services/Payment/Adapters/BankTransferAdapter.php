<?php

namespace App\Services\Payment\Adapters;

use App\Contracts\PaymentGatewayInterface;
use App\Models\BankTransferPayment;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BankTransferAdapter implements PaymentGatewayInterface
{
    public function createPayment(PaymentGateway $gateway, array $payload): PaymentResult
    {
        try {
            $orderId = $payload['order_id'] ?? 'BT-' . strtoupper(Str::random(12));

            BankTransferPayment::create([
                'order_id' => $orderId,
                'user_id' => $payload['user_id'],
                'price' => $payload['amount'],
                'price_currency' => $payload['currency'] ?? 'USD',
                'status' => 'pending',
                'type' => $payload['type'] ?? 'plan',
                'request' => json_encode($payload),
                'created_by' => $payload['user_id'],
                'attachment' => $payload['attachment'] ?? null,
            ]);

            return PaymentResult::pending($orderId, null, ['order_id' => $orderId]);
        } catch (\Exception $e) {
            return PaymentResult::failed($e->getMessage());
        }
    }

    public function verifyPayment(PaymentGateway $gateway, Request $request): PaymentResult
    {
        $orderId = $request->input('order_id');
        $payment = BankTransferPayment::where('order_id', $orderId)->first();

        if (!$payment) {
            return PaymentResult::failed('Bank transfer payment not found');
        }

        if ($payment->status === 'approved') {
            return PaymentResult::success($orderId, $payment->attachment, $payment->toArray());
        }

        return PaymentResult::failed("Payment status: {$payment->status}", $payment->toArray());
    }

    public static function configKeys(): array
    {
        return [];
    }

    public static function extraConfigKeys(): array
    {
        return [
            'instructions' => ['label' => 'Payment Instructions (HTML)', 'type' => 'richtext', 'default' => 'Please transfer to our bank account and upload the payment receipt.'],
            'bank_name' => ['label' => 'Bank Name', 'type' => 'text', 'placeholder' => 'e.g. BCA, Mandiri, BNI'],
            'account_number' => ['label' => 'Account Number', 'type' => 'text'],
            'account_holder' => ['label' => 'Account Holder Name', 'type' => 'text'],
        ];
    }
}
