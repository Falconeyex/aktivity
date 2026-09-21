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
    async download(path, body) {
        const data = await this.post(path, body);
        if (!data.content || !data.filename) {
            toast.error(t('error.generic'));
            throw new Error('Missing export payload');
        }
        const binary = atob(data.content);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i += 1) {
            bytes[i] = binary.charCodeAt(i);
        }
        const blob = new Blob([bytes], { type: data.mime || 'application/octet-stream' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = data.filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        return data;
    },
};
