const aiChat = {
    async init() {
        document.getElementById('btn-pin')?.addEventListener('click', () => this.setPinned(true));
        document.getElementById('btn-unpin')?.addEventListener('click', () => this.setPinned(false));
        document.getElementById('ai-edge')?.addEventListener('click', () => this.setPinned(true));
        document.getElementById('ai-backdrop')?.addEventListener('click', () => this.setPinned(false));
        document.getElementById('ai-send')?.addEventListener('click', () => this.send());
        document.getElementById('ai-clear')?.addEventListener('click', () => this.clear());
        document.getElementById('ai-input')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.send();
            }
        });
        try {
            const data = await api.get('ai_messages.php');
            this.render(data.messages || [], data.attached || []);
        } catch (e) {
            this.render([], []);
        }
    },

    applyPinned(pinned) {
        const app = document.querySelector('.app');
        if (!app) return;
        app.classList.toggle('ai-pinned', !!pinned);
        const pin = document.getElementById('btn-pin');
        const unpin = document.getElementById('btn-unpin');
        if (pin) pin.classList.toggle('hidden', !!pinned);
        if (unpin) unpin.classList.toggle('hidden', !pinned);
    },

    async setPinned(pinned) {
        this.applyPinned(pinned);
        const data = await api.post('settings.php', { ai_sidebar_pinned: pinned ? 1 : 0 });
        if (window.APP_SETTINGS) {
            window.APP_SETTINGS.ai_sidebar_pinned = data.settings.ai_sidebar_pinned;
        }
    },

    render(messages, attached) {
        const box = document.getElementById('ai-messages');
        if (!box) return;
        const cards = attached || [];
        const visible = (messages || []).filter((m) => m.role !== 'system');
        const parts = [];
        cards.forEach((card) => {
            const el = document.createElement('div');
            el.className = 'ai-msg user ai-card-chip';
            el.textContent = card.title || '';
            parts.push(el.outerHTML);
        });
        if (!visible.length && !cards.length) {
            parts.push(`<p class="ai-msg assistant">${t('emptyChat')}</p>`);
        }
        visible.forEach((m) => {
            const el = document.createElement('div');
            el.className = `ai-msg ${m.role}`;
            el.textContent = m.content;
            parts.push(el.outerHTML);
        });
        box.innerHTML = parts.join('');
        box.scrollTop = box.scrollHeight;
    },

    async send() {
        const input = document.getElementById('ai-input');
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        const data = await api.post('ai_chat.php', { message: text });
        this.render(data.messages || [], data.attached || []);
    },

    async clear() {
        const data = await api.post('ai_clear.php', {});
        this.render(data.messages || [], data.attached || []);
        toast.ok(t('chatCleared'));
    },

    async addCards(ids) {
        const data = await api.post('ai_add_context.php', { card_ids: ids });
        toast.ok(ids.length === 1 ? t('cardAdded') : t('cardsAdded'));
        this.render(data.messages || [], data.attached || []);
    },

    async addColumn(column) {
        const data = await api.post('ai_add_context.php', { column });
        toast.ok(t('cardsAdded'));
        this.render(data.messages || [], data.attached || []);
    },

    async addAll() {
        const data = await api.post('ai_add_context.php', { all: true });
        toast.ok(t('cardsAdded'));
        this.render(data.messages || [], data.attached || []);
    },

    async addSelected(ids) {
        if (!ids.length) return;
        await this.addCards(ids);
    },
};
