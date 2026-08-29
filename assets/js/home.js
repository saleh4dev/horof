function bindForm(id, handler) {
    const form = document.getElementById(id);
    if (!form) return;
    const msg = form.querySelector('.form-msg');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        Horof.showMsg(msg, '');
        try {
            await handler(new FormData(form), msg);
        } catch (err) {
            Horof.showMsg(msg, err.message || 'تعذر الإرسال');
        } finally {
            btn.disabled = false;
        }
    });
}

bindForm('create-form', async (fd, msg) => {
    const data = await Horof.api('create.php', {
        name: fd.get('name'),
        rounds: Number(fd.get('rounds')),
        seconds: Number(fd.get('seconds')),
    });
    if (!data.ok) {
        Horof.showMsg(msg, data.error);
        return;
    }
    Horof.setHost(data.host_token);
    location.href = data.host_url;
});

bindForm('join-form', async (fd, msg) => {
    const data = await Horof.api('join.php', {
        code: String(fd.get('code') || '').toUpperCase(),
        name: fd.get('name'),
    });
    if (!data.ok) {
        Horof.showMsg(msg, data.error);
        return;
    }
    Horof.setPlayer(data.player_token);
    location.href = data.play_url;
});
