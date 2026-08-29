bindJoin();

function bindJoin() {
    const form = document.getElementById('join-form');
    if (!form) return;
    const msg = form.querySelector('.form-msg');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        const data = await Horof.api('join.php', {
            code: String(fd.get('code') || Horof.code()).toUpperCase(),
            name: fd.get('name'),
        });
        btn.disabled = false;
        if (!data.ok) {
            Horof.showMsg(msg, data.error);
            return;
        }
        Horof.setPlayer(data.player_token);
        location.href = data.play_url;
    });
}
