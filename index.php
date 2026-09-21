<?php

declare(strict_types=1);

require __DIR__ . '/core/bootstrap.php';

$user = Auth::user();
$settings = $user ? Auth::settings((int) $user['id']) : ['language' => 'en', 'theme' => 'light', 'ai_sidebar_pinned' => 1];
$base = app_base();
$csrf = Csrf::token();
$theme = $settings['theme'] === 'dark' ? 'dark' : 'light';
$lang = $settings['language'] === 'cs' ? 'cs' : 'en';
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AKTIVITY</title>
    <?php if ($user): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
</head>
<body class="<?= $user ? '' : 'auth-body' ?>">
<?php if (!$user): ?>
    <main class="auth-page">
        <section class="auth-card">
            <h1>AKTIVITY</h1>
            <p class="lead" data-i18n="pwHint">Min. 8 characters, with uppercase, lowercase, number, and symbol.</p>
            <form id="form-login" data-auth-panel="login">
                <label class="field"><span data-i18n="email">Email</span><input type="email" name="email" required autocomplete="username"></label>
                <label class="field"><span data-i18n="password">Password</span><input type="password" name="password" required autocomplete="current-password"></label>
                <button type="submit" class="btn btn-primary" data-i18n="login">Sign in</button>
                <p class="auth-links">
                    <button type="button" class="btn btn-ghost" data-auth-tab="register" data-i18n="register">Create account</button>
                    <button type="button" class="btn btn-ghost" data-auth-tab="reset" data-i18n="forgot">Forgot password?</button>
                </p>
            </form>
            <form id="form-register" class="hidden" data-auth-panel="register">
                <label class="field"><span data-i18n="email">Email</span><input type="email" name="email" required autocomplete="username"></label>
                <label class="field"><span data-i18n="password">Password</span><input type="password" name="password" required autocomplete="new-password"></label>
                <button type="submit" class="btn btn-primary" data-i18n="register">Create account</button>
                <p class="auth-links">
                    <button type="button" class="btn btn-ghost" data-auth-tab="login" data-i18n="backToLogin">Back to sign in</button>
                </p>
            </form>
            <form id="form-reset-request" class="hidden" data-auth-panel="reset">
                <label class="field"><span data-i18n="email">Email</span><input type="email" name="email" required></label>
                <p id="reset-status" class="form-status" role="status"></p>
                <button type="submit" class="btn btn-primary" data-i18n="sendReset">Send reset link</button>
                <p><button type="button" class="btn btn-ghost" data-auth-tab="login" data-i18n="backToLogin">Back to sign in</button></p>
            </form>
        </section>
    </main>
<?php else: ?>
    <div class="app">
        <header class="header">
            <div class="brand"><span class="brand-mark">A</span> AKTIVITY</div>
            <div class="export-actions export-actions-bar">
                <button type="button" class="btn btn-export-all" data-i18n="exportAll">Export all</button>
                <button type="button" class="btn btn-export-selected" data-i18n="exportSelected">Export selected</button>
                <button type="button" class="btn btn-reminders" data-i18n="reminders">Reminders</button>
            </div>
            <button type="button" class="nav-toggle" id="btn-menu" data-i18n="menu">Menu</button>
            <div class="header-spacer"></div>
            <nav class="nav-actions">
                <div class="export-actions export-actions-menu">
                    <button type="button" class="btn btn-export-all" data-i18n="exportAll">Export all</button>
                    <button type="button" class="btn btn-export-selected" data-i18n="exportSelected">Export selected</button>
                    <button type="button" class="btn btn-reminders" data-i18n="reminders">Reminders</button>
                </div>
                <button type="button" class="btn" id="btn-board" data-i18n="board">Board</button>
                <button type="button" class="btn" id="btn-recycle" data-i18n="recycle">Recycle bin</button>
                <div class="columns-menu">
                    <button type="button" class="btn" id="btn-columns" data-i18n="columns" aria-haspopup="true" aria-expanded="false">Columns</button>
                    <div class="columns-panel hidden" id="columns-panel" role="menu">
                        <p class="columns-panel-hint" data-i18n="columnsHint">Hidden columns keep their cards. Show them again here.</p>
                        <div id="columns-panel-list"></div>
                    </div>
                </div>
                <button type="button" class="btn" id="btn-add-all" data-i18n="addAllChat">Add all to chat</button>
                <button type="button" class="btn" id="btn-add-selected" data-i18n="addSelectedChat">Add selected to chat</button>
                <button type="button" class="btn" id="btn-theme">
                    <span class="theme-when-light" data-i18n="themeDark">Dark</span>
                    <span class="theme-when-dark" data-i18n="themeLight">Light</span>
                </button>
                <button type="button" class="btn" id="btn-lang"></button>
                <span class="user-chip"><?= e($user['email']) ?></span>
                <button type="button" class="btn" id="btn-logout" data-i18n="logout">Sign out</button>
            </nav>
        </header>
        <div class="workspace">
            <div class="board-wrap">
                <div class="board" id="board"></div>
                <section class="recycle">
                    <div id="recycle-list"></div>
                </section>
            </div>
            <aside class="ai-sidebar" id="ai-sidebar">
                <div class="ai-head">
                    <h2 data-i18n="aiChat">AI chat</h2>
                    <button type="button" class="icon-btn<?= (int) $settings['ai_sidebar_pinned'] === 1 ? ' hidden' : '' ?>" id="btn-pin" data-i18n="pin" data-i18n-title="pin">Pin to board</button>
                    <button type="button" class="icon-btn<?= (int) $settings['ai_sidebar_pinned'] === 1 ? '' : ' hidden' ?>" id="btn-unpin" data-i18n="unpin" data-i18n-title="unpin">Unpin</button>
                    <button type="button" class="icon-btn" id="ai-clear" data-i18n="clearChat">Remove all content</button>
                </div>
                <div class="ai-messages" id="ai-messages"></div>
                <div class="ai-compose">
                    <input id="ai-input" type="text" data-i18n-placeholder="aiPlaceholder" maxlength="4000">
                    <button type="button" class="btn btn-primary" id="ai-send" data-i18n="send">Send</button>
                </div>
            </aside>
        </div>
        <div class="ai-backdrop" id="ai-backdrop"></div>
        <button type="button" class="btn btn-primary ai-edge" id="ai-edge" data-i18n="aiChat">AI chat</button>
    </div>

    <div class="modal-backdrop" id="modal-editor">
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-head">
                <h2 data-i18n="edit">Edit</h2>
                <button type="button" class="icon-btn" id="editor-cancel" data-i18n="close">Close</button>
            </div>
            <div class="modal-body">
                <label class="field"><span data-i18n="title">Title</span><input id="editor-title" maxlength="255"></label>
                <div class="field"><span data-i18n="body">Body</span><div id="editor-quill"></div></div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-primary" id="editor-save" data-i18n="save">Save</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="modal-history">
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-head">
                <h2 data-i18n="history">History</h2>
                <button type="button" class="icon-btn" id="history-close" data-i18n="close">Close</button>
            </div>
            <div class="modal-body" id="history-list"></div>
        </div>
    </div>

    <div class="modal-backdrop" id="modal-export">
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-head">
                <h2 data-i18n="export">Export</h2>
                <button type="button" class="icon-btn" id="export-cancel" data-i18n="close">Close</button>
            </div>
            <div class="modal-body">
                <p class="export-format-hint" data-i18n="exportFormat">Choose export format</p>
                <div class="export-formats">
                    <label><input type="radio" name="export-format" value="json" checked> <span data-i18n="exportJson">JSON</span></label>
                    <label><input type="radio" name="export-format" value="html"> <span data-i18n="exportHtml">HTML</span></label>
                    <label><input type="radio" name="export-format" value="xml"> <span data-i18n="exportXml">XML</span></label>
                    <label><input type="radio" name="export-format" value="csv"> <span data-i18n="exportCsv">CSV</span></label>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-primary" id="export-confirm" data-i18n="export">Export</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="modal-reminder">
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-head">
                <h2 data-i18n="addReminder">Add reminder</h2>
                <button type="button" class="icon-btn" id="reminder-cancel" data-i18n="close">Close</button>
            </div>
            <div class="modal-body">
                <label class="field">
                    <span data-i18n="reminderWhen">Date and time</span>
                    <input id="reminder-when" type="datetime-local">
                </label>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" id="reminder-clear" data-i18n="reminderClear">Clear reminder</button>
                <button type="button" class="btn btn-primary" id="reminder-save" data-i18n="reminderSave">Save reminder</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="modal-reminders">
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-head">
                <h2 data-i18n="reminders">Reminders</h2>
                <button type="button" class="icon-btn" id="reminders-close" data-i18n="close">Close</button>
            </div>
            <div class="modal-body" id="reminders-list"></div>
        </div>
    </div>
<?php endif; ?>

    <div class="toast-wrap" aria-live="polite"></div>
    <script>
        window.APP_BASE = <?= json_encode($base, JSON_UNESCAPED_SLASHES) ?>;
        window.CSRF = <?= json_encode($csrf) ?>;
        window.APP_SETTINGS = <?= json_encode($settings, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="<?= e(asset_url('js/i18n.js')) ?>"></script>
    <script src="<?= e(asset_url('js/toast.js')) ?>"></script>
    <script src="<?= e(asset_url('js/api.js')) ?>"></script>
    <script src="<?= e(asset_url('js/auth.js')) ?>"></script>
<?php if ($user): ?>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
    <script src="<?= e(asset_url('js/kanban.js')) ?>"></script>
    <script src="<?= e(asset_url('js/export.js')) ?>"></script>
    <script src="<?= e(asset_url('js/ai.js')) ?>"></script>
<?php endif; ?>
    <script src="<?= e(asset_url('js/app.js')) ?>"></script>
</body>
</html>
