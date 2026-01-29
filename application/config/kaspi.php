<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Kaspi Webhook Configuration
|--------------------------------------------------------------------------
|
| Configure the Kaspi webhook integration settings.
|
*/

$config['kaspi_webhook_header'] = defined('Config::KASPI_WEBHOOK_HEADER') ? Config::KASPI_WEBHOOK_HEADER : 'X-Kaspi-Signature';
$config['kaspi_webhook_token'] = defined('Config::KASPI_WEBHOOK_TOKEN') ? Config::KASPI_WEBHOOK_TOKEN : '';
$config['kaspi_paid_statuses'] = ['paid', 'success'];
$config['kaspi_default_service_id'] = defined('Config::KASPI_DEFAULT_SERVICE_ID') ? Config::KASPI_DEFAULT_SERVICE_ID : 0;
$config['kaspi_default_provider_id'] = defined('Config::KASPI_DEFAULT_PROVIDER_ID') ? Config::KASPI_DEFAULT_PROVIDER_ID : 0;
