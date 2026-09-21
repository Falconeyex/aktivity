document.addEventListener('DOMContentLoaded', () => {
    const lang = window.APP_SETTINGS?.language || 'en';
    const theme = window.APP_SETTINGS?.theme || 'light';
    document.documentElement.dataset.theme = theme;
    setLang(lang);
    document.getElementById('btn-theme')?.addEventListener('click', async () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        syncChromeLabels();
        try {
            await api.post('settings.php', { theme: next });
            if (window.APP_SETTINGS) window.APP_SETTINGS.theme = next;
        } catch (e) {
            /* label already matches the visible theme */
        }
    });
    document.getElementById('btn-lang')?.addEventListener('click', async () => {
        const next = currentLang === 'en' ? 'cs' : 'en';
        setLang(next);
        if (document.getElementById('board')) {
            kanban.renderBoard();
            kanban.renderColumnPanel();
            if (kanban.view === 'recycle') kanban.renderBin();
        }
        try {
            await api.post('settings.php', { language: next });
            if (window.APP_SETTINGS) window.APP_SETTINGS.language = next;
        } catch (e) {
            /* UI language already switched */
        }
    });
    document.getElementById('btn-menu')?.addEventListener('click', () => {
        document.querySelector('.header')?.classList.toggle('menu-open');
    });
    document.getElementById('btn-logout')?.addEventListener('click', async () => {
        await api.post('logout.php', {});
        window.location.reload();
    });
    syncChromeLabels();

    if (document.getElementById('form-login') || document.getElementById('form-register')) {
        authPage.init();
    }
    if (document.getElementById('form-reset-confirm')) {
        resetPage.init();
    }
    if (document.getElementById('board')) {
        const desktop = window.matchMedia('(min-width: 1280px)');
        const savedPinned = Number(window.APP_SETTINGS?.ai_sidebar_pinned) === 1;
        aiChat.applyPinned(desktop.matches ? savedPinned : false);
        Promise.all([kanban.init(), aiChat.init()]).catch(() => {});
        exporter.init();
    }
});

function syncChromeLabels() {
    const langBtn = document.getElementById('btn-lang');
    if (langBtn) {
        langBtn.textContent = currentLang === 'en' ? t('langCs') : t('langEn');
    }
}
