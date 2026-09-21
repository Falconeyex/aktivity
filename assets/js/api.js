const api = {
    async request(path, options = {}) {
        const opts = { ...options };
        const headers = {
            Accept: 'application/json',
            'X-CSRF-Token': window.CSRF || '',
            ...(opts.headers || {}),
        };
        const init = {
            credentials: 'same-origin',
            method: opts.method || 'GET',
            headers,
        };
        if (opts.body !== undefined) {
            headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(opts.body);
        }
        let res;
        try {
            res = await fetch(`${window.APP_BASE}/api/${path}`, init);
        } catch (e) {
            toast.error(t('error.network'));
            throw e;
        }
        let data = {};
        try {
            data = await res.json();
        } catch (e) {
            data = {};
        }
        if (data.csrf) {
            window.CSRF = data.csrf;
        }
        if (!res.ok) {
            toast.error(data.message || `${t('error.generic')} (${res.status})`);
            const err = new Error(data.message || 'Request failed');
            err.status = res.status;
            err.data = data;
            throw err;
        }
        return data;
    },
    get(path) {
        return this.request(path);
    },
    post(path, body) {
        return this.request(path, { method: 'POST', body });
    },
};
