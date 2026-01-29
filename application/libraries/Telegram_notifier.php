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
 * Telegram notifier library.
 *
 * Sends Telegram notifications for new appointments.
 *
 * @package Libraries
 */
class Telegram_notifier
{
    /**
     * Send a new appointment notification to Telegram.
     *
     * @param array $appointment
     * @param array $customer
     *
     * @return bool
     */
    public function send_new_appointment(array $appointment, array $customer): bool
    {
        $message = $this->format_message($appointment, $customer);

        return $this->send_message($message);
    }

    /**
     * Send a message to Telegram.
     *
     * @param string $message
     *
     * @return bool
     */
    protected function send_message(string $message): bool
    {
        $token = (string) config('telegram_bot_token', '');
        $chat_id = (string) config('telegram_chat_id', '');

        if ($token === '' || $chat_id === '') {
            return false;
        }

        $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';

        $payload = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => config('telegram_parse_mode', 'HTML'),
            'disable_web_page_preview' => true,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $http_code < 200 || $http_code >= 300) {
            log_message('error', 'Telegram notification failed: ' . ($response ?: 'No response'));

            return false;
        }

        return true;
    }

    /**
     * Format a new appointment message.
     *
     * @param array $appointment
     * @param array $customer
     *
     * @return string
     */
    protected function format_message(array $appointment, array $customer): string
    {
        $full_name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $full_name = $full_name !== '' ? $full_name : 'Unknown';
        $phone = $customer['phone_number'] ?? '';
        $email = $customer['email'] ?? '';
        $start = $appointment['start_datetime'] ?? '';
        $end = $appointment['end_datetime'] ?? '';
        $payment_id = $appointment['payment_id'] ?? '';

        $lines = [
            '<b>Новая запись после оплаты Kaspi</b>',
            'Клиент: ' . $this->escape($full_name),
            'Телефон: ' . $this->escape($phone ?: '-'),
            'Email: ' . $this->escape($email ?: '-'),
            'Время: ' . $this->escape($start . ' - ' . $end),
        ];

        if ($payment_id !== '') {
            $lines[] = 'Платеж: ' . $this->escape($payment_id);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Escape text for Telegram HTML mode.
     *
     * @param string $value
     *
     * @return string
     */
    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
