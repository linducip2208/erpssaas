<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\GatewayFormat;
use App\Models\PaymentGateway;
use App\Services\Payment\Adapters\BankTransferAdapter;
use App\Services\Payment\Adapters\PayPalAdapter;
use App\Services\Payment\Adapters\RedirectAdapter;
use App\Services\Payment\Adapters\RestApiAdapter;
use App\Services\Payment\Adapters\StripeAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PaymentGatewayService
{
    private array $adapters = [];

    public function __construct()
    {
        $this->adapters = [
            GatewayFormat::Stripe->value => new StripeAdapter,
            GatewayFormat::PayPal->value => new PayPalAdapter,
            GatewayFormat::Redirect->value => new RedirectAdapter,
            GatewayFormat::RestApi->value => new RestApiAdapter,
            GatewayFormat::BankTransfer->value => new BankTransferAdapter,
        ];
    }

    public function getEnabledGateways(): Collection
    {
        return PaymentGateway::enabled()->forTenant()->orderBy('sort_order')->get();
    }

    public function getGatewayById(int $id): ?PaymentGateway
    {
        return PaymentGateway::forTenant()->find($id);
    }

    public function getGatewayByName(string $name): ?PaymentGateway
    {
        return PaymentGateway::enabled()->forTenant()->where('name', $name)->first();
    }

    public function createPayment(PaymentGateway|int|string $gateway, array $payload): PaymentResult
    {
        $gateway = $this->resolveGateway($gateway);
        if (!$gateway) {
            return PaymentResult::failed('Payment gateway not found or disabled');
        }

        $adapter = $this->getAdapter($gateway->format);
        if (!$adapter) {
            return PaymentResult::failed("Unsupported gateway format: {$gateway->format}");
        }

        return $adapter->createPayment($gateway, $payload);
    }

    public function verifyPayment(PaymentGateway|int|string $gateway, Request $request): PaymentResult
    {
        $gateway = $this->resolveGateway($gateway);
        if (!$gateway) {
            return PaymentResult::failed('Payment gateway not found');
        }

        $adapter = $this->getAdapter($gateway->format);
        if (!$adapter) {
            return PaymentResult::failed("Unsupported gateway format: {$gateway->format}");
        }

        return $adapter->verifyPayment($gateway, $request);
    }

    public function getAdapter(string $format): ?PaymentGatewayInterface
    {
        return $this->adapters[$format] ?? null;
    }

    public function getAdapterConfigKeys(string $format): array
    {
        $adapter = $this->getAdapter($format);
        if (!$adapter) {
            return [];
        }
        return [
            'required' => $adapter::configKeys(),
            'extra' => $adapter::extraConfigKeys(),
        ];
    }

    public function getAllFormats(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
        ], GatewayFormat::cases());
    }

    private function resolveGateway(PaymentGateway|int|string $gateway): ?PaymentGateway
    {
        if ($gateway instanceof PaymentGateway) {
            return $gateway;
        }
        if (is_int($gateway)) {
            return $this->getGatewayById($gateway);
        }
        return $this->getGatewayByName($gateway);
    }
}
