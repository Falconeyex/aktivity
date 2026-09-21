<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require __DIR__ . '/ai_lib.php';
require_method('POST');
require_csrf();
require_auth();

ai_clear_state();
json_ok(ai_payload());
