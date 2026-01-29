# Kaspi → Telegram Webhook Integration

This guide explains how to accept Kaspi payment webhooks, create appointments, and notify a Telegram bot.

## 1) Configure the app

1. Copy `config-sample.php` to `config.php` (if not already done).
2. Fill in the following constants:

```php
const KASPI_WEBHOOK_HEADER = 'X-Kaspi-Signature';
const KASPI_WEBHOOK_TOKEN = 'your-secret-token';
const KASPI_DEFAULT_SERVICE_ID = 1;
const KASPI_DEFAULT_PROVIDER_ID = 2;

const TELEGRAM_BOT_TOKEN = '123456:ABC...';
const TELEGRAM_CHAT_ID = '-1001234567890';
```

> You can pass `service_id` and `provider_id` in the webhook payload. The defaults are used when they are missing.

## 2) Webhook endpoint

The Kaspi webhook endpoint is:

```
POST /webhooks/kaspi
```

The secret token is checked using the `KASPI_WEBHOOK_HEADER` header.

Example:

```
X-Kaspi-Signature: your-secret-token
```

## 3) Payload format

Send JSON with the following structure:

```json
{
  "status": "paid",
  "payment_id": "kaspi-123",
  "appointment": {
    "service_id": 1,
    "provider_id": 2,
    "start_datetime": "2024-06-25 15:00:00",
    "end_datetime": "2024-06-25 15:30:00",
    "notes": "Kaspi order #123"
  },
  "customer": {
    "first_name": "Amina",
    "last_name": "K.",
    "phone": "+7 777 000 00 00",
    "email": "amina@example.com"
  }
}
```

If the status is not in `kaspi_paid_statuses`, the webhook responds with `"ignored": true`.

## 4) Telegram message

When the appointment is created, a Telegram message is sent to the configured chat with:

- Customer name
- Phone/email
- Appointment time
- Payment ID (if provided)
