<?php

namespace App\Enums;

enum GatewayFormat: string
{
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case Redirect = 'redirect';
    case RestApi = 'rest_api';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe-compatible (Checkout Session)',
            self::PayPal => 'PayPal REST API',
            self::Redirect => 'Redirect-based (Snap, Hosted Payment)',
            self::RestApi => 'REST API with HMAC/Token Auth',
            self::BankTransfer => 'Manual Bank Transfer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Stripe => 'Covers: Stripe, and any provider exposing a Stripe-compatible Checkout Session API.',
            self::PayPal => 'Covers: PayPal REST API v2 with OAuth2 token exchange.',
            self::Redirect => 'Covers: Midtrans Snap, ToyyibPay, SenangPay, Billplz — redirect to hosted payment page, callback with signature verification.',
            self::RestApi => 'Covers: Xendit, Razorpay, Paystack, Flutterwave, Mollie, PayMongo, Duitku, Tripay, PhonePe, iyzico — REST API with API key / HMAC auth.',
            self::BankTransfer => 'Manual bank transfer with payment proof upload and admin approval.',
        };
    }
}
