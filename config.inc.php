<?php

define('BASE_PATH', dirname(__FILE__) . '/');
define('LOG_ID', gethostname() . '-' . uniqid('REQ-'));

define('REQUEST_TOKEN', '<TOKEN>');

define('DB_HOST', '<HOST>');
define('DB_PORT', '<PORT>');
define('DB_NAME', 'bonus');
define('DB_USERNAME', '<USERNAME>');
define('DB_PASSWORD', '<PASSWORD>');

define('SMTP_HOST', '<SMTP_HOST>');
define('SMTP_PORT', '<SMTP_PORT>');
define('SMTP_USERNAME', '<SMTP_USERNAME>');
define('SMTP_PASSWORD', '<SMTP_PASSWORD>');
define('SMTP_FROM', '<SMTP_FROM>');
define('SMTP_FROMNAME', '<SMTP_FROMNAME>');

define('SLACK_API_TOKEN', '<SLACK_API_TOKEN>');

mb_internal_encoding('UTF-8');
ini_set('error_log', BASE_PATH . 'log/prim-' . date('Y-m-d') . '.log');

$SLACK_ADMIN_USERNAMES = array(
    "myslack.username1",
    "myslack.username1"
);

$ADMIN_MAILS = array(
    "admin1@example.com",
    "admin2@example.com"
);
