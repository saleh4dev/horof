const lobby = document.getElementById('lobby-show');
const play = document.getElementById('play-show');
const results = document.getElementById('results-show');

function boardHtml(players, withRound, withWords) {
    return players.filter((p) => !p.kicked).map((p, i) => {
        const words = withWords && p.words
            ? `<div class="muted">${p.words.map((w) => Horof.esc(w.word)).join('، ')}</div>`
            : '';
        const round = withRound ? ` / الجولة ${p.round_score} (${p.round_words} كلمات)` : '';
        return `<li>
            <span><span class="rank">${i + 1}</span> ${Horof.esc(p.name)}${words}</span>
            <span class="pts">${p.total_score}${round}</span>
        </li>`;
    }).join('');
}

function render(data) {
    const room = data.room;
    document.getElementById('disp-name').textContent = room.name || room.host_name || 'حروف';
    document.getElementById('disp-code').textContent = room.code;
    document.getElementById('disp-round').textContent =
        room.status === 'lobby' ? `بانتظار المتسابقين · ${room.total_rounds} جولات` :
        room.status === 'finished' ? 'النتيجة النهائية' :
        `الجولة ${room.round} من ${room.total_rounds}`;
    document.getElementById('disp-timer').textContent =
        room.status === 'playing' ? room.seconds_left :
        room.status === 'results' ? `الانتقال خلال ${room.results_left}` : '';

    lobby.hidden = room.status !== 'lobby';
    play.hidden = room.status !== 'playing';
    results.hidden = room.status !== 'results' && room.status !== 'finished';

    if (room.status === 'lobby') {
        const qr = document.getElementById('disp-qr');
        if (qr.dataset.url !== data.share_url) {
            qr.dataset.url = data.share_url;
            qr.src = Horof.qrUrl(data.share_url);
        }
        document.getElementById('disp-lobby-players').innerHTML =
            data.players.filter((p) => !p.kicked).map((p) => `<li>${Horof.esc(p.name)}</li>`).join('');
    }
    if (room.status === 'playing') {
        Horof.renderLetters(document.getElementById('disp-letters'), room.letters);
        document.getElementById('disp-live-board').innerHTML = boardHtml(data.players, false, false);
    }
    if (room.status === 'results' || room.status === 'finished') {
        document.getElementById('results-title').textContent =
            room.status === 'finished' ? 'ترتيب المتسابقين النهائي' : `نتيجة الجولة ${room.round}`;
        document.getElementById('disp-results').innerHTML = boardHtml(data.players, true, true);
    }
}

async function tick() {
    const data = await Horof.state();
    if (data.ok) render(data);
}

tick();
setInterval(tick, Horof.pollMs);
