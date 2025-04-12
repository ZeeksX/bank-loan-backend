<?php
// File: config/mailer.php

return [
    'host' => $_ENV['MAIL_HOST'],
    'port' => $_ENV['MAIL_PORT'],
    'encryption' => $_ENV['MAIL_ENCRYPTION'],
    'username' => $_ENV['MAIL_USERNAME'],
    'password' => $_ENV['MAIL_PASSWORD'],
    'from_email' => $_ENV['MAIL_FROM_ADDRESS'],
    'from_name' => $_ENV['MAIL_FROM_NAME'],
    'to_email' => $_ENV['MAIL_TO_ADDRESS'],
];