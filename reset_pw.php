<?php

declare(strict_types=1);

require __DIR__ . '/core/bootstrap.php';

$settings = ['language' => 'en', 'theme' => 'light', 'ai_sidebar_pinned' => 1];
$base = app_base();
$csrf = Csrf::token();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AKTIVITY — Reset password</title>
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
</head>
<body class="auth-body">
    <main class="auth-page">
        <section class="auth-card">
            <h1 data-i18n="resetTitle">Set a new password</h1>
            <p class="lead" data-i18n="resetLead">Choose a strong password. This link expires 10 minutes after it was sent.</p>
            <form id="form-reset-confirm">
                <label class="field"><span data-i18n="newPassword">New password</span><input type="password" name="password" required autocomplete="new-password"></label>
                <p class="lead" data-i18n="pwHint"></p>
                <button type="submit" class="btn btn-primary" data-i18n="savePassword">Update password</button>
            </form>
            <p><a href="<?= e($base) ?>/index.php" data-i18n="backToLogin">Back to sign in</a></p>
        </section>
    </main>
    <div class="toast-wrap" aria-live="polite"></div>
    <script>
        window.APP_BASE = <?= json_encode($base, JSON_UNESCAPED_SLASHES) ?>;
        window.CSRF = <?= json_encode($csrf) ?>;
        window.APP_SETTINGS = <?= json_encode($settings) ?>;
    </script>
    <script src="<?= e(asset_url('js/i18n.js')) ?>"></script>
    <script src="<?= e(asset_url('js/toast.js')) ?>"></script>
    <script src="<?= e(asset_url('js/api.js')) ?>"></script>
    <script src="<?= e(asset_url('js/auth.js')) ?>"></script>
    <script src="<?= e(asset_url('js/app.js')) ?>"></script>
</body>
</html>
