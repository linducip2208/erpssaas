<?php

namespace Workdo\WhatsApp\Services;

use Illuminate\Support\Facades\Http;
use Workdo\WhatsApp\Models\WhatsAppLog;

class WhatsAppService
{
    protected $baseUrl;
    protected $apiKey;
    protected $phoneNumberId;

    public function __construct()
    {
        $this->baseUrl = getSetting('whatsapp_base_url', creatorId());
        $this->apiKey = getSetting('whatsapp_api_key', creatorId());
        $this->phoneNumberId = getSetting('whatsapp_phone_number_id', creatorId());
    }

    public function send($to, $message, $templateId = null, $variables = [])
    {
        $log = WhatsAppLog::create([
            'to_number' => $to,
            'message' => $message,
            'template_id' => $templateId,
            'status' => 'pending',
            'sent_by' => auth()->id() ?? null,
        ]);

        if (empty($this->baseUrl) || empty($this->apiKey)) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'WhatsApp API not configured',
            ]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post(rtrim($this->baseUrl, '/') . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

            $log->update([
                'status' => $response->successful() ? 'sent' : 'failed',
                'response' => $response->json(),
                'error_message' => !$response->successful() ? ($response->json()['error']['message'] ?? 'Unknown error') : null,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
