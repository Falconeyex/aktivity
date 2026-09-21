const COLUMNS = ['backlog', 'todo', 'in_progress', 'review', 'done', 'postponed'];

const kanban = {
    cards: [],
    bin: [],
    selected: new Set(),
    view: 'board',
    editor: null,
    reminderCard: null,
    quill: null,
    sortables: [],

    async init() {
        this.bindUi();
        await this.reload();
    },

    bindUi() {
        document.getElementById('btn-board')?.addEventListener('click', () => this.setView('board'));
        document.getElementById('btn-recycle')?.addEventListener('click', () => this.setView('recycle'));
        document.getElementById('btn-add-all')?.addEventListener('click', () => aiChat.addAll());
        document.getElementById('btn-add-selected')?.addEventListener('click', () => aiChat.addSelected([...this.selected]));
        document.getElementById('btn-columns')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleColumnsPanel();
        });
        document.getElementById('columns-panel')?.addEventListener('click', (e) => e.stopPropagation());
        document.addEventListener('click', () => this.closeColumnsPanel());
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeColumnsPanel();
        });
        document.getElementById('editor-save')?.addEventListener('click', () => this.saveEditor());
        document.getElementById('editor-cancel')?.addEventListener('click', () => this.closeModal('modal-editor'));
        document.getElementById('history-close')?.addEventListener('click', () => this.closeModal('modal-history'));
        document.querySelectorAll('.btn-reminders').forEach((btn) => {
            btn.addEventListener('click', () => this.openReminders());
        });
        document.getElementById('reminders-close')?.addEventListener('click', () => this.closeModal('modal-reminders'));
        document.getElementById('reminder-cancel')?.addEventListener('click', () => this.closeModal('modal-reminder'));
        document.getElementById('reminder-save')?.addEventListener('click', () => this.saveReminder(false));
        document.getElementById('reminder-clear')?.addEventListener('click', () => this.saveReminder(true));
    },

    setView(view) {
        this.view = view;
        document.querySelector('.app')?.classList.toggle('view-recycle', view === 'recycle');
        if (view === 'recycle') {
            this.loadBin();
        }
    },

    async reload() {
        const data = await api.get('cards.php');
        this.cards = data.cards || [];
        this.renderBoard();
        if (this.view === 'recycle') {
            await this.loadBin();
        }
    },

    async loadBin() {
        const data = await api.get('cards.php?deleted=1');
        this.bin = data.cards || [];
        this.renderBin();
    },

    hiddenColumns() {
        const list = window.APP_SETTINGS?.hidden_columns;
        return Array.isArray(list) ? list.filter((col) => COLUMNS.includes(col)) : [];
    },

    visibleColumns() {
        const hidden = new Set(this.hiddenColumns());
        return COLUMNS.filter((col) => !hidden.has(col));
    },

    toggleColumnsPanel() {
        const panel = document.getElementById('columns-panel');
        if (!panel) return;
        const open = panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !open);
        document.getElementById('btn-columns')?.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) this.renderColumnPanel();
    },

    closeColumnsPanel() {
        document.getElementById('columns-panel')?.classList.add('hidden');
        document.getElementById('btn-columns')?.setAttribute('aria-expanded', 'false');
    },

    renderColumnPanel() {
        const list = document.getElementById('columns-panel-list');
        if (!list) return;
        const hidden = new Set(this.hiddenColumns());
        list.innerHTML = '';
        COLUMNS.forEach((col) => {
            const row = document.createElement('label');
            row.className = 'columns-panel-row';
            const box = document.createElement('input');
            box.type = 'checkbox';
            box.checked = !hidden.has(col);
            box.addEventListener('change', () => this.setColumnHidden(col, !box.checked));
            const name = document.createElement('span');
            name.textContent = t('col_' + col);
            row.appendChild(box);
            row.appendChild(name);
            list.appendChild(row);
        });
        this.syncColumnsButton();
    },

    async setColumnHidden(col, hide) {
        if (!COLUMNS.includes(col)) return;
        let hidden = this.hiddenColumns();
        if (hide) {
            if (!hidden.includes(col)) hidden = [...hidden, col];
            if (hidden.length >= COLUMNS.length) {
                toast.error(t('keepOneColumn'));
                this.renderColumnPanel();
                return;
            }
        } else {
            hidden = hidden.filter((item) => item !== col);
        }
        try {
            const data = await api.post('settings.php', { hidden_columns: hidden });
            if (window.APP_SETTINGS) {
                window.APP_SETTINGS.hidden_columns = data.settings.hidden_columns || hidden;
            }
        } catch (e) {
            this.renderColumnPanel();
            return;
        }
        this.renderBoard();
        this.renderColumnPanel();
        this.syncColumnsButton();
    },

    renderBoard() {
        const board = document.getElementById('board');
        if (!board) return;
        const visible = this.visibleColumns();
        board.innerHTML = '';
        board.dataset.visibleCount = String(visible.length);
        visible.forEach((col) => {
            const items = this.cards.filter((c) => c.status_column === col);
            const el = document.createElement('section');
            el.className = 'column';
            el.dataset.column = col;
            el.innerHTML = `
                <div class="column-head">
                    <div class="column-label">
                        <h2 class="column-title" data-i18n="col_${col}">${t('col_' + col)}</h2>
                        <div class="column-count">${items.length}</div>
                    </div>
                    <div class="column-actions">
                        <button type="button" class="icon-btn" data-add="${col}" title="${t('addCard')}">+</button>
                        <button type="button" class="icon-btn" data-col-chat="${col}" title="${t('addColumnChat')}">AI</button>
                        <button type="button" class="icon-btn column-hide" data-hide="${col}" title="${t('hide')}" aria-label="${t('hide')}" ${visible.length <= 1 ? 'disabled' : ''}>X</button>
                    </div>
                </div>
                <div class="column-body" data-col-body="${col}"></div>
            `;
            const body = el.querySelector('.column-body');
            items.forEach((card) => body.appendChild(this.cardEl(card)));
            el.querySelector('[data-add]').addEventListener('click', () => this.openEditor({ status_column: col }));
            el.querySelector('[data-col-chat]').addEventListener('click', () => aiChat.addColumn(col));
            el.querySelector('[data-hide]').addEventListener('click', () => this.setColumnHidden(col, true));
            board.appendChild(el);
        });
        this.bindSortables();
        this.syncColumnsButton();
    },

    syncColumnsButton() {
        const btn = document.getElementById('btn-columns');
        if (!btn) return;
        const hidden = this.hiddenColumns().length;
        btn.textContent = hidden ? `${t('columns')} (${hidden})` : t('columns');
        btn.classList.toggle('has-hidden', hidden > 0);
    },

    cardEl(card) {
        const el = document.createElement('article');
        el.className = 'card' + (this.selected.has(Number(card.id)) ? ' selected' : '');
        el.dataset.id = String(card.id);
        const created = `${t('created')}: ${card.created_at}`;
        el.innerHTML = `
            <div class="card-top">
                <input type="checkbox" class="card-select" ${this.selected.has(Number(card.id)) ? 'checked' : ''} aria-label="select">
                <span class="card-handle" title="Drag">⋮⋮</span>
                <h3 class="card-title"></h3>
            </div>
            <div class="card-body ql-snow"><div class="ql-editor"></div></div>
            <div class="card-meta">
                <div class="card-sub"></div>
            </div>
            <button type="button" class="card-reminder ${this.reminderClass(card.remind_at)}" data-act="reminder">${this.reminderCaption(card.remind_at)}</button>
            <div class="card-actions">
                <button type="button" class="icon-btn" data-act="edit">${t('edit')}</button>
                <button type="button" class="icon-btn" data-act="history">${t('history')}</button>
                <button type="button" class="icon-btn" data-act="ai">${t('addToChat')}</button>
                <button type="button" class="icon-btn" data-act="delete">${t('recycle')}</button>
                <label class="card-move">
                    <select class="card-move-select" aria-label="${t('moveTo')}"></select>
                </label>
            </div>
        `;
        el.querySelector('.card-title').textContent = card.title;
        el.querySelector('.card-body .ql-editor').innerHTML = card.body || '';
        el.querySelector('.card-sub').textContent = created;
        const moveSelect = el.querySelector('.card-move-select');
        COLUMNS.forEach((col) => {
            const opt = document.createElement('option');
            opt.value = col;
            opt.textContent = t('col_' + col);
            if (col === card.status_column) opt.selected = true;
            moveSelect.appendChild(opt);
        });
        moveSelect.addEventListener('click', (e) => e.stopPropagation());
        moveSelect.addEventListener('mousedown', (e) => e.stopPropagation());
        moveSelect.addEventListener('touchstart', (e) => e.stopPropagation(), { passive: true });
        moveSelect.addEventListener('change', (e) => {
            e.stopPropagation();
            this.moveToColumn(card.id, moveSelect.value);
        });
        el.querySelector('.card-select').addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleSelect(Number(card.id));
        });
        el.querySelector('[data-act="edit"]').addEventListener('click', (e) => {
            e.stopPropagation();
            this.openEditor(card);
        });
        el.querySelector('[data-act="history"]').addEventListener('click', (e) => {
            e.stopPropagation();
            this.openHistory(card.id);
        });
        el.querySelector('[data-act="ai"]').addEventListener('click', (e) => {
            e.stopPropagation();
            aiChat.addCards([card.id]);
        });
        el.querySelector('[data-act="delete"]').addEventListener('click', (e) => {
            e.stopPropagation();
            this.remove(card.id);
        });
        el.querySelector('[data-act="reminder"]').addEventListener('click', (e) => {
            e.stopPropagation();
            this.openReminder(card);
        });
        el.addEventListener('dblclick', () => this.openEditor(card));
        return el;
    },

    toggleSelect(id) {
        if (this.selected.has(id)) this.selected.delete(id);
        else this.selected.add(id);
        this.renderBoard();
    },

    bindSortables() {
        this.sortables.forEach((s) => s.destroy());
        this.sortables = [];
        if (typeof Sortable === 'undefined') return;
        document.querySelectorAll('[data-col-body]').forEach((body) => {
            this.sortables.push(new Sortable(body, {
                group: 'kanban',
                handle: '.card-handle',
                animation: 150,
                delay: 180,
                delayOnTouchOnly: true,
                ghostClass: 'sortable-ghost',
                onEnd: () => this.persistOrder(),
            }));
        });
    },

    async moveToColumn(cardId, column) {
        const card = this.cards.find((c) => Number(c.id) === Number(cardId));
        if (!card || card.status_column === column || !COLUMNS.includes(column)) {
            return;
        }
        const destCount = this.cards.filter((c) => c.status_column === column).length;
        try {
            const data = await api.post('cards_move.php', {
                items: [{
                    id: Number(cardId),
                    status_column: column,
                    order_index: destCount,
                }],
            });
            this.cards = data.cards || this.cards;
            toast.ok(`${t('cardMoved')}: ${t('col_' + column)}`);
            this.renderBoard();
        } catch (e) {
            this.renderBoard();
        }
    },

    async persistOrder() {
        const items = [];
        document.querySelectorAll('[data-col-body]').forEach((body) => {
            const column = body.dataset.colBody;
            [...body.querySelectorAll('.card')].forEach((card, index) => {
                items.push({
                    id: Number(card.dataset.id),
                    status_column: column,
                    order_index: index,
                });
            });
        });
        const data = await api.post('cards_move.php', { items });
        this.cards = data.cards || this.cards;
        this.renderBoard();
    },

    openEditor(card) {
        this.editor = card || { status_column: 'backlog' };
        const modal = document.getElementById('modal-editor');
        document.getElementById('editor-title').value = card?.title || '';
        this.ensureQuill();
        this.quill.root.innerHTML = card?.body || '';
        modal.classList.add('open');
        document.getElementById('editor-title').focus();
    },

    ensureQuill() {
        if (this.quill || typeof Quill === 'undefined') {
            return;
        }
        this.quill = new Quill('#editor-quill', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ header: [1, 2, 3, false] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    ['clean'],
                ],
            },
        });
    },

    closeModal(id) {
        document.getElementById(id)?.classList.remove('open');
    },

    parseRemindAt(value) {
        if (!value) return null;
        const raw = String(value).trim();
        const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
        const date = new Date(normalized);
        return Number.isNaN(date.getTime()) ? null : date;
    },

    reminderOverdue(value) {
        const date = this.parseRemindAt(value);
        return !!(date && date.getTime() < Date.now());
    },

    reminderClass(value) {
        if (!this.parseRemindAt(value)) return 'unset';
        return this.reminderOverdue(value) ? 'overdue' : 'upcoming';
    },

    formatRemindAt(value) {
        const date = this.parseRemindAt(value);
        if (!date) return '';
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
    },

    toDateTimeLocal(value) {
        const date = this.parseRemindAt(value);
        if (!date) return '';
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    },

    reminderCaption(value) {
        if (!this.parseRemindAt(value)) return t('addReminder');
        return `${t('remindAt')} ${this.formatRemindAt(value)}`;
    },

    openReminder(card) {
        this.reminderCard = card;
        const input = document.getElementById('reminder-when');
        if (input) input.value = this.toDateTimeLocal(card.remind_at);
        const clear = document.getElementById('reminder-clear');
        if (clear) clear.classList.toggle('hidden', !this.parseRemindAt(card.remind_at));
        document.getElementById('modal-reminder')?.classList.add('open');
        input?.focus();
    },

    async saveReminder(clear) {
        if (!this.reminderCard?.id) return;
        const input = document.getElementById('reminder-when');
        const value = clear ? null : (input?.value || '').trim();
        if (!clear && !value) {
            toast.error(t('reminderRequired'));
            return;
        }
        const data = await api.post('cards_reminder.php', {
            id: this.reminderCard.id,
            remind_at: value,
        });
        const next = data.card;
        this.cards = this.cards.map((card) => (
            Number(card.id) === Number(next.id) ? next : card
        ));
        toast.ok(t('saved'));
        this.closeModal('modal-reminder');
        this.renderBoard();
        this.renderRemindersList();
    },

    openReminders() {
        this.renderRemindersList();
        document.getElementById('modal-reminders')?.classList.add('open');
    },

    renderRemindersList() {
        const box = document.getElementById('reminders-list');
        if (!box) return;
        const items = this.cards
            .filter((card) => this.parseRemindAt(card.remind_at))
            .sort((a, b) => this.parseRemindAt(a.remind_at) - this.parseRemindAt(b.remind_at));
        if (!items.length) {
            box.innerHTML = `<p>${t('reminderEmpty')}</p>`;
            return;
        }
        box.innerHTML = '';
        items.forEach((card) => {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = `reminder-item ${this.reminderClass(card.remind_at)}`;
            const title = document.createElement('strong');
            title.textContent = card.title;
            const when = document.createElement('span');
            when.textContent = `${t('remindAt')} ${this.formatRemindAt(card.remind_at)}`;
            row.appendChild(title);
            row.appendChild(when);
            row.addEventListener('click', () => {
                this.closeModal('modal-reminders');
                this.openReminder(card);
            });
            box.appendChild(row);
        });
    },

    async saveEditor() {
        const title = document.getElementById('editor-title').value.trim();
        const body = this.quill ? this.quill.root.innerHTML : '';
        if (this.editor?.id) {
            await api.post('cards_update.php', { id: this.editor.id, title, body });
        } else {
            await api.post('cards_create.php', {
                title,
                body,
                status_column: this.editor.status_column || 'backlog',
            });
        }
        toast.ok(t('saved'));
        this.closeModal('modal-editor');
        await this.reload();
    },

    async openHistory(id) {
        const data = await api.get(`cards_history.php?id=${encodeURIComponent(id)}`);
        const box = document.getElementById('history-list');
        const rows = data.history || [];
        if (!rows.length) {
            box.innerHTML = `<p>${t('historyEmpty')}</p>`;
        } else {
            box.innerHTML = rows.map((row) => {
                const prev = row.previous_state?.status_column || '';
                const next = row.new_state?.status_column || '';
                const move = (prev || next) ? ` (${prev} → ${next})` : '';
                return `<article class="history-item">
                    <strong>${t('action_' + row.action_type)}${move}</strong>
                    <time>${row.timestamp}</time>
                </article>`;
            }).join('');
        }
        document.getElementById('modal-history').classList.add('open');
    },

    async remove(id) {
        await api.post('cards_delete.php', { id });
        this.selected.delete(Number(id));
        toast.ok(t('movedBin'));
        await this.reload();
    },

    renderBin() {
        const box = document.getElementById('recycle-list');
        if (!box) return;
        if (!this.bin.length) {
            box.innerHTML = `<p>${t('emptyBin')}</p>`;
            return;
        }
        box.innerHTML = '';
        this.bin.forEach((card) => {
            const el = document.createElement('article');
            el.className = 'recycle-item';
            el.innerHTML = `
                <div>
                    <strong></strong>
                    <div class="recycle-meta"></div>
                </div>
                <div class="card-actions">
                    <button type="button" class="btn btn-soft" data-act="restore">${t('restore')}</button>
                    <button type="button" class="btn btn-danger" data-act="purge">${t('purge')}</button>
                </div>
            `;
            el.querySelector('strong').textContent = card.title;
            el.querySelector('.recycle-meta').textContent = `${t('col_' + card.status_column)} · ${card.deleted_at}`;
            el.querySelector('[data-act="restore"]').addEventListener('click', async () => {
                await api.post('cards_restore.php', { id: card.id });
                toast.ok(t('restored'));
                await this.reload();
            });
            el.querySelector('[data-act="purge"]').addEventListener('click', async () => {
                await api.post('cards_purge.php', { id: card.id });
                toast.ok(t('purged'));
                await this.reload();
            });
            box.appendChild(el);
        });
    },
};
