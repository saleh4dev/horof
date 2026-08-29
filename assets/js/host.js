const msg = document.getElementById('host-msg');
const list = document.getElementById('player-list');
let lastShare = '';

async function action(path) {
    const data = await Horof.api(path, {});
    if (!data.ok) {
        Horof.showMsg(msg, data.error);
        return;
    }
    Horof.showMsg(msg, '');
    render(data);
}

document.getElementById('btn-start').addEventListener('click', () => action('start.php'));
document.getElementById('btn-end-round').addEventListener('click', () => action('end_round.php'));
document.getElementById('btn-end-game').addEventListener('click', () => {
    if (confirm('إنهاء المسابقة الآن؟')) action('end_game.php');
});
document.getElementById('copy-link').addEventListener('click', async () => {
    if (!lastShare) return;
    try {
        await navigator.clipboard.writeText(lastShare);
        Horof.showMsg(msg, 'تم نسخ رابط المشاركة', true);
    } catch {
        Horof.showMsg(msg, lastShare, true);
    }
});

function render(data) {
    const room = data.room;
    lastShare = data.share_url;
    document.getElementById('room-code').textContent = room.code;
    document.getElementById('share-url').textContent = data.share_url;
    document.getElementById('display-link').href = data.display_url;
    document.getElementById('whatsapp-link').href =
        'https://wa.me/?text=' + encodeURIComponent('انضم لمسابقة حروف: ' + data.share_url);
    const qr = document.getElementById('qr-image');
    if (qr.dataset.url !== data.share_url) {
        qr.dataset.url = data.share_url;
        qr.src = Horof.qrUrl(data.share_url);
    }
    document.getElementById('status-label').textContent = Horof.statusText(room.status);
    document.getElementById('round-label').textContent =
        room.round ? `الجولة ${room.round} من ${room.total_rounds}` : `عدد الجولات: ${room.total_rounds}`;
    document.getElementById('timer-label').textContent =
        room.status === 'playing' ? `${room.seconds_left} ث` :
        room.status === 'results' ? `الجولة التالية خلال ${room.results_left} ث` : '';
    Horof.renderLetters(document.getElementById('letters'), room.letters);
    document.getElementById('btn-start').hidden = room.status !== 'lobby';
    document.getElementById('btn-end-round').hidden = room.status !== 'playing';
    document.getElementById('btn-end-game').hidden = room.status === 'finished';
    document.getElementById('lobby-hint').hidden = room.status !== 'lobby';

    const active = data.players.filter((p) => !p.kicked);
    document.getElementById('player-count').textContent = active.length;
    list.innerHTML = data.players.map((p) => {
        const kick = p.kicked
            ? '<span class="kicked">مطرود</span>'
            : `<button type="button" class="kick" data-id="${p.id}">طرد</button>`;
        return `<li class="${p.kicked ? 'kicked' : ''}">
            <span>${Horof.esc(p.name)}</span>
            <span>${p.total_score} نقطة · ${kick}</span>
        </li>`;
    }).join('') || '<li class="muted">لا يوجد متسابقون بعد</li>';
}

list.addEventListener('click', async (e) => {
    const btn = e.target.closest('.kick');
    if (!btn) return;
    const data = await Horof.api('kick.php', { player_id: Number(btn.dataset.id) });
    if (!data.ok) Horof.showMsg(msg, data.error);
    else render(data);
});

async function tick() {
    const data = await Horof.state();
    if (!data.ok) {
        Horof.showMsg(msg, data.error);
        return;
    }
    if (!data.is_host) {
        Horof.showMsg(msg, 'هذه الصفحة للقائد الذي أنشأ الغرفة من هذا المتصفح.');
        return;
    }
    render(data);
}

tick();
setInterval(tick, 1000);
