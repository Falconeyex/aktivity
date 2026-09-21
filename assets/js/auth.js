const authPage = {
    busy: false,
    init() {
        const loginForm = document.getElementById('form-login');
        const registerForm = document.getElementById('form-register');
        const resetForm = document.getElementById('form-reset-request');
        document.querySelectorAll('[data-auth-tab]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.show(btn.dataset.authTab);
                if (btn.dataset.authTab === 'reset' && loginForm && resetForm) {
                    const loginEmail = (loginForm.email?.value || '').trim();
                    if (loginEmail) {
                        resetForm.email.value = loginEmail;
                    }
                    resetForm.email.focus();
                }
            });
        });
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.submit('login.php', loginForm);
            });
        }
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.submit('register.php', registerForm);
            });
        }
        if (resetForm) {
            resetForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.requestReset(resetForm);
            });
        }
    },
    show(name) {
        document.querySelectorAll('[data-auth-panel]').forEach((el) => {
            el.classList.toggle('hidden', el.dataset.authPanel !== name);
        });
        document.querySelectorAll('[data-auth-tab]').forEach((el) => {
            el.classList.toggle('active', el.dataset.authTab === name);
        });
    },
    setResetStatus(message, type) {
        const status = document.getElementById('reset-status');
        if (!status) return;
        status.textContent = message;
        status.className = `form-status ${type || ''}`;
    },
    async requestReset(form) {
        if (this.busy) return;
        const email = (form.email?.value || '').trim();
        const button = form.querySelector('button[type="submit"]');
        if (!email) {
            this.setResetStatus(t('emailRequired'), 'error');
            toast.error(t('emailRequired'));
            return;
        }
        this.busy = true;
        if (button) {
            button.disabled = true;
            button.textContent = t('sending');
        }
        this.setResetStatus(t('sending'), '');
        try {
            const data = await api.post('reset_request.php', { email });
            const message = data.message || t('resetSent');
            this.setResetStatus(message, 'ok');
            toast.ok(message);
        } catch (e) {
            const message = e.message || t('resetFailed');
            this.setResetStatus(message, 'error');
        } finally {
            this.busy = false;
            if (button) {
                button.disabled = false;
                button.textContent = t('sendReset');
            }
        }
    },
    async submit(endpoint, form) {
        const button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        try {
            await api.post(endpoint, {
                email: form.email.value.trim(),
                password: form.password.value,
            });
            window.location.reload();
        } catch (e) {
            if (button) button.disabled = false;
        }
    },
};

const resetPage = {
    init() {
        const form = document.getElementById('form-reset-confirm');
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const token = new URLSearchParams(window.location.search).get('token') || '';
            const button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;
            try {
                await api.post('reset_confirm.php', {
                    token,
                    password: form.password.value,
                });
                toast.ok(t('saved'));
                setTimeout(() => {
                    window.location.href = `${window.APP_BASE}/index.php`;
                }, 800);
            } catch (err) {
                if (button) {
                    button.disabled = false;
                    button.textContent = t('savePassword');
                }
            }
        });
    },
};
