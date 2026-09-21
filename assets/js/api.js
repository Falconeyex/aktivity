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
        let res;
        try {
            res = await fetch(`${window.APP_BASE}/api/${path}`, {
                credentials: 'same-origin',
                method: 'POST',
                headers: {
                    Accept: '*/*',
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF || '',
                },
                body: JSON.stringify(body),
            });
        } catch (e) {
            toast.error(t('error.network'));
            throw e;
        }
        const type = res.headers.get('Content-Type') || '';
        if (type.includes('application/json')) {
            let data = {};
            try {
                data = await res.json();
            } catch (e) {
                data = {};
            }
            if (data.csrf) window.CSRF = data.csrf;
            if (!res.ok) {
                toast.error(data.message || `${t('error.generic')} (${res.status})`);
                const err = new Error(data.message || 'Request failed');
                err.status = res.status;
                err.data = data;
                throw err;
            }
            return data;
        }
        if (!res.ok) {
            toast.error(`${t('error.generic')} (${res.status})`);
            throw new Error('Request failed');
        }
        const blob = await res.blob();
        const disp = res.headers.get('Content-Disposition') || '';
        const match = /filename="([^"]+)"/i.exec(disp);
        const name = match ? match[1] : 'aktivity-export';
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = name;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    },
};
