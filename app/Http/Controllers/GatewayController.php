<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GatewayController extends Controller
{
    public function __construct(
        private PaymentGatewayService $gatewayService,
    ) {}

    public function index()
    {
        if (!Auth::user()->can('manage-settings')) {
            return back()->with('error', __('Permission denied'));
        }

        $gateways = PaymentGateway::forTenant()->orderBy('sort_order')->get();

        return Inertia::render('gateways/index', [
            'gateways' => $gateways,
            'formats' => $this->gatewayService->getAllFormats(),
            'presets' => $this->getPresets(),
        ]);
    }

    public function create()
    {
        if (!Auth::user()->can('manage-settings')) {
            return back()->with('error', __('Permission denied'));
        }

        return Inertia::render('gateways/create', [
            'formats' => $this->gatewayService->getAllFormats(),
            'presets' => $this->getPresets(),
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('manage-settings')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:payment_gateways,name',
            'format' => 'required|string',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'base_url' => 'nullable|url|max:500',
            'api_key' => 'nullable|string|max:2000',
            'api_secret' => 'nullable|string|max:2000',
            'webhook_secret' => 'nullable|string|max:2000',
            'extra_config' => 'nullable|json',
            'supported_currencies' => 'nullable|array',
            'is_enabled' => 'boolean',
            'is_test_mode' => 'boolean',
            'logo' => 'nullable|string|max:500',
            'sort_order' => 'integer',
        ]);

        PaymentGateway::create([
            ...$validated,
            'created_by' => creatorId(),
        ]);

        return redirect()->route('gateways.index')->with('success', __('Payment gateway added successfully.'));
    }

    public function edit(PaymentGateway $gateway)
    {
        if (!Auth::user()->can('manage-settings')) {
            return back()->with('error', __('Permission denied'));
        }

        return Inertia::render('gateways/edit', [
            'gateway' => $gateway,
            'formats' => $this->gatewayService->getAllFormats(),
            'configKeys' => $this->gatewayService->getAdapterConfigKeys($gateway->format),
            'presets' => $this->getPresets(),
        ]);
    }

    public function update(Request $request, PaymentGateway $gateway)
    {
        if (!Auth::user()->can('manage-settings')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:payment_gateways,name,' . $gateway->id,
            'format' => 'required|string',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'base_url' => 'nullable|url|max:500',
            'api_key' => 'nullable|string|max:2000',
            'api_secret' => 'nullable|string|max:2000',
            'webhook_secret' => 'nullable|string|max:2000',
            'extra_config' => 'nullable|json',
            'supported_currencies' => 'nullable|array',
            'is_enabled' => 'boolean',
            'is_test_mode' => 'boolean',
            'logo' => 'nullable|string|max:500',
            'sort_order' => 'integer',
        ]);

        if (empty($validated['api_secret'])) {
            unset($validated['api_secret']);
        }
        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        $gateway->update($validated);

        return redirect()->route('gateways.index')->with('success', __('Payment gateway updated successfully.'));
    }

    public function destroy(PaymentGateway $gateway)
    {
        if (!Auth::user()->can('manage-settings')) {
            return back()->with('error', __('Permission denied'));
        }

        $gateway->delete();

        return redirect()->route('gateways.index')->with('success', __('Payment gateway deleted successfully.'));
    }

    public function toggle(PaymentGateway $gateway)
    {
        if (!Auth::user()->can('manage-settings')) {
            return back()->with('error', __('Permission denied'));
        }

        $gateway->update(['is_enabled' => !$gateway->is_enabled]);

        return back()->with('success', __('Gateway status toggled.'));
    }

    public function getKeys(Request $request)
    {
        $format = $request->input('format');
        if (!$format) {
            return response()->json([]);
        }

        return response()->json($this->gatewayService->getAdapterConfigKeys($format));
    }

    public function preset(Request $request)
    {
        $name = $request->input('name');
        $preset = $this->getPreset($name);

        if (!$preset) {
            return response()->json(['error' => 'Preset not found'], 404);
        }

        return response()->json($preset);
    }

    private function getPresets(): array
    {
        $presetPath = storage_path('app/payment-presets');
        if (!is_dir($presetPath)) {
            return [];
        }

        $presets = [];
        foreach (glob($presetPath . '/*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $presets[] = $data;
            }
        }

        return $presets;
    }

    private function getPreset(string $name): ?array
    {
        $file = storage_path("app/payment-presets/{$name}.json");
        if (!file_exists($file)) {
            return null;
        }
        return json_decode(file_get_contents($file), true);
    }
}
