const msg = document.getElementById('play-msg');
const form = document.getElementById('word-form');
const input = document.getElementById('word-input');

if (!Horof.playerToken) {
    location.href = 'join.php?c=' + encodeURIComponent(Horof.code());
} else {
    tick();
    setInterval(tick, 1000);
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const word = input.value.trim();
    if (!word) return;
    const data = await Horof.api('submit.php', { word });
    if (!data.ok) {
        Horof.showMsg(msg, data.error);
        return;
    }
    input.value = '';
    Horof.showMsg(msg, `+${data.points} نقطة`, true);
    render(data);
    input.focus();
});

function render(data) {
    const room = data.room;
    const you = data.you;
    if (!you) {
        location.href = 'join.php?c=' + encodeURIComponent(room.code);
        return;
    }
    if (you.kicked) {
        document.querySelector('.play-layout').innerHTML = '<section class="panel"><h1>تم طردك من المسابقة</h1></section>';
        return;
    }
    document.getElementById('player-name').textContent = you.name;
    document.getElementById('round-title').textContent =
        room.status === 'lobby' ? 'بانتظار البدء' :
        room.status === 'finished' ? 'النتيجة النهائية' :
        `الجولة ${room.round} من ${room.total_rounds}`;
    document.getElementById('timer').textContent =
        room.status === 'playing' ? room.seconds_left :
        room.status === 'results' ? room.results_left :
        room.status === 'lobby' ? '—' : '';
    Horof.renderLetters(document.getElementById('letters'), room.letters);
    form.hidden = room.status !== 'playing';
    if (you) {
        document.getElementById('my-words').innerHTML =
            (you.words || []).map((w) => `<li>${Horof.esc(w.word)} · ${w.points}</li>`).join('');
        const me = data.players.find((p) => p.id === you.id);
        document.getElementById('score-line').textContent = me
            ? `هذه الجولة: ${me.round_words} كلمات / ${me.round_score} نقطة — المجموع: ${me.total_score}`
            : '';
    }
    document.getElementById('board').innerHTML = data.players.filter((p) => !p.kicked).map((p, i) => {
        const extra = room.status === 'results' || room.status === 'finished'
            ? ` · الجولة ${p.round_score}`
            : '';
        const words = p.words ? `<small>${p.words.map((w) => Horof.esc(w.word)).join('، ')}</small>` : '';
        return `<li>
            <span><span class="rank">${i + 1}</span> ${Horof.esc(p.name)} ${words}</span>
            <span class="pts">${p.total_score}${extra}</span>
        </li>`;
    }).join('');
}

async function tick() {
    const data = await Horof.state();
    if (!data.ok) {
        Horof.showMsg(msg, data.error);
        return;
    }
    render(data);
}
