<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require __DIR__ . '/ai_lib.php';
$user = require_auth();

json_ok(ai_payload());
