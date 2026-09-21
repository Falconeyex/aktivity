<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();

Auth::logout();
json_ok(['csrf' => Csrf::token()]);
