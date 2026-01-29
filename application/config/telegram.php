<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Telegram Configuration
|--------------------------------------------------------------------------
|
| Configure the Telegram bot notifications.
|
*/

$config['telegram_bot_token'] = defined('Config::TELEGRAM_BOT_TOKEN') ? Config::TELEGRAM_BOT_TOKEN : '';
$config['telegram_chat_id'] = defined('Config::TELEGRAM_CHAT_ID') ? Config::TELEGRAM_CHAT_ID : '';
$config['telegram_parse_mode'] = 'HTML';
