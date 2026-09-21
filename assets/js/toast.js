const toast = {
    box: null,
    ensure() {
        if (!this.box) {
            this.box = document.querySelector('.toast-wrap');
        }
        return this.box;
    },
    show(message, type = 'info') {
        const wrap = this.ensure();
        if (!wrap) return;
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.textContent = message;
        wrap.appendChild(el);
        setTimeout(() => el.remove(), 4200);
    },
    error(message) {
        this.show(message, 'error');
    },
    ok(message) {
        this.show(message, 'ok');
    },
};
