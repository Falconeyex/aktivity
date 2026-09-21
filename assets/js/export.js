const exporter = {
    mode: 'all',

    init() {
        document.querySelectorAll('.btn-export-all').forEach((btn) => {
            btn.addEventListener('click', () => this.open('all'));
        });
        document.querySelectorAll('.btn-export-selected').forEach((btn) => {
            btn.addEventListener('click', () => this.open('selected'));
        });
        document.getElementById('export-cancel')?.addEventListener('click', () => this.close());
        document.getElementById('export-confirm')?.addEventListener('click', () => this.run());
    },

    open(mode) {
        if (mode === 'selected' && kanban.selected.size < 1) {
            toast.error(t('exportNone'));
            return;
        }
        this.mode = mode;
        const first = document.querySelector('input[name="export-format"][value="json"]');
        if (first) first.checked = true;
        document.getElementById('modal-export')?.classList.add('open');
    },

    close() {
        document.getElementById('modal-export')?.classList.remove('open');
    },

    async run() {
        const format = document.querySelector('input[name="export-format"]:checked')?.value || 'json';
        const payload = this.mode === 'all'
            ? { all: true, format }
            : { format, card_ids: [...kanban.selected] };
        const button = document.getElementById('export-confirm');
        if (button) button.disabled = true;
        try {
            await api.download('cards_export.php', payload);
            this.close();
        } finally {
            if (button) button.disabled = false;
        }
    },
};
