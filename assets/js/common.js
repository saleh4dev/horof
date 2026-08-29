const Horof = {
    pollMs: 4000,
    hostToken: localStorage.getItem('horof_host') || '',
    playerToken: localStorage.getItem('horof_player') || '',

    setHost(token) {
        this.hostToken = token;
        this.playerToken = '';
        localStorage.setItem('horof_host', token);
        localStorage.removeItem('horof_player');
    },

    setPlayer(token) {
        this.playerToken = token;
        this.hostToken = '';
        localStorage.setItem('horof_player', token);
        localStorage.removeItem('horof_host');
    },

    code() {
        const q = new URLSearchParams(location.search).get('c');
        return (q || document.body.dataset.code || '').toUpperCase();
    },

    async api(path, body) {
        const headers = { Accept: 'application/json' };
        if (this.hostToken) headers['X-Host-Token'] = this.hostToken;
        if (this.playerToken) headers['X-Player-Token'] = this.playerToken;
        const opts = { method: body ? 'POST' : 'GET', headers };
        if (body) {
            headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const url = body ? `api/${path}` : `api/${path}${path.includes('?') ? '' : ''}`;
        const res = await fetch(url, opts);
        const data = await res.json().catch(() => ({ ok: false, error: 'تعذر قراءة الرد' }));
        if (!res.ok && !data.error) data.error = 'حدث خطأ في الاتصال';
        return data;
    },

    state() {
        const code = this.code();
        return this.api(`state.php?c=${encodeURIComponent(code)}`);
    },

    showMsg(el, text, ok) {
        if (!el) return;
        el.hidden = !text;
        el.textContent = text || '';
        el.classList.toggle('ok', !!ok);
    },

    qrUrl(text) {
        return `https://api.qrserver.com/v1/create-qr-code/?size=360x360&margin=10&data=${encodeURIComponent(text)}`;
    },

    renderLetters(el, letters) {
        if (!el) return;
        el.innerHTML = (letters || []).map((ch) => `<span class="tile">${this.esc(ch)}</span>`).join('');
    },

    statusText(status) {
        return {
            lobby: 'الانتظار',
            playing: 'جولة جارية',
            results: 'نتائج الجولة',
            finished: 'انتهت المسابقة',
        }[status] || status;
    },

    esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[ch]));
    },
};
