(function () {
    const form = document.getElementById('add-word-form');
    if (!form) return;
    const letters = (form.dataset.letters || '').replace(/\s+/g, '');
    const input = document.getElementById('new-word');
    const hint = document.getElementById('word-hint');

    function counts(text) {
        const map = {};
        for (const ch of Array.from(text)) {
            map[ch] = (map[ch] || 0) + 1;
        }
        return map;
    }

    function extras(word) {
        const bag = counts(letters);
        const need = {};
        for (const ch of Array.from(word.replace(/\s+/g, ''))) {
            if (bag[ch]) {
                bag[ch] -= 1;
            } else {
                need[ch] = (need[ch] || 0) + 1;
            }
        }
        return need;
    }

    input.addEventListener('input', () => {
        const word = input.value.trim();
        if (!word) {
            hint.hidden = true;
            return;
        }
        const need = extras(word);
        const missing = Object.keys(need);
        const ok = missing.length === 0;
        hint.hidden = false;
        hint.classList.toggle('ok', ok);
        if (ok) {
            hint.textContent = 'تُستخرج من هذه الحروف';
            return;
        }
        hint.textContent = 'تنقص هذه الحروف: ' + missing.map((ch) => (
            need[ch] > 1 ? ch + ' × ' + need[ch] : ch
        )).join('، ');
    });
})();
