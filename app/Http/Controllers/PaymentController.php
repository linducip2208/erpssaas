<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentGatewayService $gatewayService,
    ) {}

    public function pay(Request $request, string $gatewayName)
    {
        $gateway = $this->gatewayService->getGatewayByName($gatewayName);
        if (!$gateway) {
            return back()->with('error', __('Payment gateway not found or disabled.'));
        }

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'duration' => 'required|in:Month,Year',
            'modules' => 'nullable|string',
            'coupon_code' => 'nullable|string',
            'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $duration = $validated['duration'];

        $planPrice = ($duration === 'Year')
            ? $plan->package_price_yearly
            : $plan->package_price_monthly;

        $modulePrice = 0;
        $userModule = $validated['modules'] ?? '';
        if (!empty($userModule)) {
            foreach (explode(',', $userModule) as $module) {
                $modulePrice += ($duration === 'Year')
                    ? ModulePriceByName($module)['yearly_price']
                    : ModulePriceByName($module)['monthly_price'];
            }
        }

        $amount = $planPrice + $modulePrice;

        if (!empty($validated['coupon_code'])) {
            $couponResult = applyCouponDiscount($validated['coupon_code'], $amount, Auth::id());
            if ($couponResult['valid']) {
                $amount = $couponResult['final_amount'];
            }
        }

        $orderId = 'ORD-' . strtoupper(Str::random(14));

        // Handle attachment upload for bank transfer
        $attachment = null;
        if ($request->hasFile('payment_receipt')) {
            $upload = upload_file($request, 'payment_receipt', $orderId . '.' . $request->file('payment_receipt')->extension(), 'bank_transfer');
            if ($upload['flag'] == 1) {
                $attachment = $upload['url'];
            }
        }

        if ($amount <= 0) {
            $counter = ['user_counter' => -1, 'storage_counter' => 0];
            $assignPlan = assignPlan($plan->id, $duration, $userModule, $counter, Auth::id());

            if ($assignPlan['is_success']) {
                Order::create([
                    'order_id' => $orderId,
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'plan_name' => $plan->name,
                    'plan_id' => $plan->id,
                    'price' => 0,
                    'discount_amount' => ($planPrice + $modulePrice) - $amount,
                    'currency' => admin_setting('defaultCurrency') ?? 'USD',
                    'txn_id' => '',
                    'payment_type' => $gateway->display_name ?? $gateway->name,
                    'payment_status' => 'succeeded',
                    'receipt' => null,
                    'created_by' => Auth::id(),
                ]);

                if (!empty($validated['coupon_code'])) {
                    $coupon = Coupon::where('code', $validated['coupon_code'])->first();
                    if ($coupon) recordCouponUsage($coupon->id, Auth::id(), $orderId);
                }

                return redirect()->route('plans.index')->with('success', __('Plan activated successfully!'));
            }

            return redirect()->route('plans.index')->with('error', __('Something went wrong.'));
        }

        $payload = [
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => admin_setting('defaultCurrency') ?? 'USD',
            'plan_name' => $plan->name,
            'description' => "{$plan->name} - {$duration}",
            'customer_name' => Auth::user()->name,
            'customer_email' => Auth::user()->email,
            'user_id' => Auth::id(),
            'type' => 'plan',
            'attachment' => $attachment,
            'metadata' => [
                'plan_id' => $plan->id,
                'duration' => $duration,
                'modules' => $userModule,
                'coupon_code' => $validated['coupon_code'] ?? '',
            ],
            'success_url' => route('payment.callback', ['gateway' => $gateway->name]) . '?order_id=' . $orderId,
            'cancel_url' => route('plans.index') . '?payment=cancelled',
            'callback_url' => route('payment.webhook', ['gateway' => $gateway->name]),
        ];

        $result = $this->gatewayService->createPayment($gateway, $payload);

        if (!$result->success) {
            return back()->with('error', $result->errorMessage ?? __('Payment failed.'));
        }

        // Store payment context in session
        session([
            'payment_gateway' => $gateway->name,
            'payment_gateway_order_id' => $result->gatewayOrderId,
            'payment_order_id' => $orderId,
            'payment_plan_id' => $plan->id,
            'payment_duration' => $duration,
            'payment_modules' => $userModule,
            'payment_amount' => $amount,
            'payment_coupon' => $validated['coupon_code'] ?? '',
            'payment_currency' => admin_setting('defaultCurrency') ?? 'USD',
        ]);

        // Save Order as pending
        Order::create([
            'order_id' => $orderId,
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'plan_name' => $plan->name,
            'plan_id' => $plan->id,
            'price' => $amount,
            'discount_amount' => ($planPrice + $modulePrice) - $amount,
            'currency' => admin_setting('defaultCurrency') ?? 'USD',
            'txn_id' => $result->gatewayOrderId,
            'payment_type' => $gateway->display_name ?? $gateway->name,
            'payment_status' => 'pending',
            'receipt' => null,
            'created_by' => Auth::id(),
        ]);

        if ($result->redirectUrl) {
            return redirect()->away($result->redirectUrl);
        }

        if ($result->status === 'pending') {
            return redirect()->route('plans.index')->with('success', __('Payment request submitted. Awaiting verification.'));
        }

        return redirect()->route('plans.index')->with('info', __('Payment is being processed.'));
    }

    public function callback(Request $request, string $gatewayName)
    {
        $gateway = $this->gatewayService->getGatewayByName($gatewayName);
        if (!$gateway) {
            return redirect()->route('plans.index')->with('error', __('Payment gateway not found.'));
        }

        $result = $this->gatewayService->verifyPayment($gateway, $request);

        if (!$result->success) {
            $orderId = $request->get('order_id') ?? session('payment_order_id');
            if ($orderId) {
                Order::where('order_id', $orderId)->update(['payment_status' => 'failed']);
            }
            session()->forget(['payment_gateway', 'payment_gateway_order_id', 'payment_order_id', 'payment_plan_id', 'payment_duration', 'payment_modules', 'payment_amount', 'payment_coupon', 'payment_currency']);
            return redirect()->route('plans.index')->with('error', __('Payment was not successful. Please try again.'));
        }

        $orderId = $request->get('order_id') ?? session('payment_order_id');
        $planId = session('payment_plan_id');
        $duration = session('payment_duration');
        $modules = session('payment_modules');
        $couponCode = session('payment_coupon');

        $order = Order::where('order_id', $orderId)->first();
        if ($order) {
            $order->update([
                'payment_status' => 'succeeded',
                'txn_id' => $result->gatewayOrderId ?? $order->txn_id,
                'receipt' => $result->receiptUrl,
            ]);
        }

        $counter = ['user_counter' => -1, 'storage_counter' => 0];
        $assignPlan = assignPlan($planId, $duration, $modules, $counter, Auth::id());

        if ($assignPlan['is_success']) {
            if ($couponCode) {
                $coupon = Coupon::where('code', $couponCode)->first();
                if ($coupon) recordCouponUsage($coupon->id, Auth::id(), $orderId);
            }
            session()->forget(['payment_gateway', 'payment_gateway_order_id', 'payment_order_id', 'payment_plan_id', 'payment_duration', 'payment_modules', 'payment_amount', 'payment_coupon', 'payment_currency']);
            return redirect()->route('plans.index')->with('success', __('Plan activated successfully!'));
        }

        session()->forget(['payment_gateway', 'payment_gateway_order_id', 'payment_order_id', 'payment_plan_id', 'payment_duration', 'payment_modules', 'payment_amount', 'payment_coupon', 'payment_currency']);
        return redirect()->route('plans.index')->with('error', __('Payment succeeded but plan activation failed. Please contact support.'));
    }

    public function status(Request $request, string $gatewayName)
    {
        $gateway = $this->gatewayService->getGatewayByName($gatewayName);
        if (!$gateway) {
            return response()->json(['error' => 'Gateway not found'], 404);
        }

        $result = $this->gatewayService->verifyPayment($gateway, $request);
        return response()->json([
            'success' => $result->success,
            'status' => $result->status,
            'gateway_order_id' => $result->gatewayOrderId,
            'error' => $result->errorMessage,
        ]);
    }

    public function webhook(Request $request, string $gatewayName)
    {
        $gateway = $this->gatewayService->getGatewayByName($gatewayName);
        if (!$gateway) {
            return response()->json(['error' => 'Gateway not found'], 404);
        }

        $result = $this->gatewayService->verifyPayment($gateway, $request);

        if ($result->success) {
            $metadata = $result->rawResponse['metadata'] ?? [];
            $orderId = $metadata['order_id'] ?? $request->get('order_id');
            if ($orderId) {
                $order = Order::where('order_id', $orderId)->first();
                if ($order && $order->payment_status !== 'succeeded') {
                    $order->update([
                        'payment_status' => 'succeeded',
                        'txn_id' => $result->gatewayOrderId ?? $order->txn_id,
                        'receipt' => $result->receiptUrl,
                    ]);

                    $planId = $order->plan_id;
                    if ($planId) {
                        assignPlan($planId, null, null, ['user_counter' => -1, 'storage_counter' => 0], $order->created_by);
                    }
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
