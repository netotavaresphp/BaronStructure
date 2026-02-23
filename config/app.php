<?php
return [
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
];
