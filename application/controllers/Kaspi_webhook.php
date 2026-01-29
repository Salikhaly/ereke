<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.2
 * ---------------------------------------------------------------------------- */

/**
 * Kaspi webhook controller.
 *
 * Handles incoming payment webhooks and creates appointments.
 *
 * @package Controllers
 */
class Kaspi_webhook extends EA_Controller
{
    /**
     * Kaspi_webhook constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('customers_model');

        $this->load->library('telegram_notifier');

        $this->config->load('kaspi');
        $this->config->load('telegram');
    }

    /**
     * Handle payment webhook.
     */
    public function payment(): void
    {
        try {
            if ($this->input->method(true) !== 'POST') {
                abort(405, 'Method Not Allowed');
            }

            $payload = json_decode($this->input->raw_input_stream, true);

            if (!is_array($payload)) {
                throw new InvalidArgumentException('Invalid JSON payload.');
            }

            $this->assert_webhook_token();

            $status = strtolower((string)($payload['status'] ?? $payload['payment_status'] ?? ''));
            $paid_statuses = array_map('strtolower', config('kaspi_paid_statuses', ['paid']));

            if ($status === '') {
                throw new InvalidArgumentException('Payment status is missing.');
            }

            if (!in_array($status, $paid_statuses, true)) {
                json_response([
                    'success' => true,
                    'ignored' => true,
                    'status' => $status,
                ]);

                return;
            }

            $appointment_payload = $payload['appointment'] ?? [];
            $customer_payload = $payload['customer'] ?? [];

            $service_id = $appointment_payload['service_id'] ?? $payload['service_id'] ?? config('kaspi_default_service_id');
            $provider_id = $appointment_payload['provider_id'] ?? $payload['provider_id'] ?? config('kaspi_default_provider_id');
            $start_datetime = $appointment_payload['start_datetime'] ?? $payload['start_datetime'] ?? null;
            $end_datetime = $appointment_payload['end_datetime'] ?? $payload['end_datetime'] ?? null;

            if (empty($service_id) || empty($provider_id) || empty($start_datetime) || empty($end_datetime)) {
                throw new InvalidArgumentException('Missing appointment data: service_id, provider_id, start_datetime, end_datetime.');
            }

            $customer = [
                'first_name' => $customer_payload['first_name'] ?? $payload['first_name'] ?? '',
                'last_name' => $customer_payload['last_name'] ?? $payload['last_name'] ?? '',
                'email' => $customer_payload['email'] ?? $payload['email'] ?? '',
                'phone_number' => $customer_payload['phone_number']
                    ?? $customer_payload['phone']
                    ?? $payload['phone_number']
                    ?? $payload['phone']
                    ?? '',
                'notes' => $customer_payload['notes'] ?? $payload['customer_notes'] ?? '',
                'language' => config('language'),
            ];

            $customer_id = $this->customers_model->save($customer);

            $payment_id = $payload['payment_id'] ?? $payload['id'] ?? '';
            $notes = $appointment_payload['notes'] ?? $payload['notes'] ?? '';

            if (!empty($payment_id)) {
                $notes = trim($notes . PHP_EOL . 'Kaspi payment ID: ' . $payment_id);
            }

            if ($notes === '') {
                $notes = 'Kaspi payment confirmed.';
            }

            $appointment = [
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'id_services' => (int) $service_id,
                'id_users_provider' => (int) $provider_id,
                'id_users_customer' => (int) $customer_id,
                'is_unavailability' => false,
                'notes' => $notes,
            ];

            $appointment_id = $this->appointments_model->save($appointment);

            $telegram_sent = $this->telegram_notifier->send_new_appointment([
                'appointment_id' => $appointment_id,
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'service_id' => $service_id,
                'provider_id' => $provider_id,
                'payment_id' => $payment_id,
            ], $customer);

            json_response([
                'success' => true,
                'appointment_id' => $appointment_id,
                'customer_id' => $customer_id,
                'telegram_sent' => $telegram_sent,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Validate webhook secret token header.
     */
    protected function assert_webhook_token(): void
    {
        $expected_token = (string) config('kaspi_webhook_token', '');

        if ($expected_token === '') {
            return;
        }

        $header_name = (string) config('kaspi_webhook_header', 'X-Kaspi-Signature');
        $provided_token = (string) $this->input->get_request_header($header_name, true);

        if ($provided_token === '' || !hash_equals($expected_token, $provided_token)) {
            abort(401, 'Invalid webhook token.');
        }
    }
}
