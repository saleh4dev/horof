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

    function fits(word) {
        const bag = counts(letters);
        for (const ch of Array.from(word.replace(/\s+/g, ''))) {
            if (!bag[ch]) return false;
            bag[ch] -= 1;
        }
        return true;
    }

    input.addEventListener('input', () => {
        const word = input.value.trim();
        if (!word) {
            hint.hidden = true;
            return;
        }
        const ok = fits(word);
        hint.hidden = false;
        hint.textContent = ok ? 'تُستخرج من هذه الحروف' : 'لا يمكن تكوينها من الحروف المعروضة';
        hint.classList.toggle('ok', ok);
    });
})();
