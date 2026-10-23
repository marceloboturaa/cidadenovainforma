(() => {
    const picker = document.querySelector('[data-article-picker]');
    if (!picker) return;
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const search = picker.querySelector('input[type="search"]');
    const options = [...picker.querySelectorAll('[data-article-option]')];
    const more = picker.querySelector('[data-more-articles]');
    let limit = 8;
    const updateSelection = () => {
        const selected = options.find(option => option.querySelector('input').checked);
        const preview = picker.querySelector('[data-selected-article]');
        preview.hidden = !selected;
        if (!selected) return;
        preview.querySelector('[data-selected-title]').textContent = selected.querySelector('strong').textContent;
        const label = selected.querySelector('small').textContent;
        preview.querySelector('[data-selected-link]').href = label.substring(label.indexOf(' · ') + 3);
    };
    const filter = () => {
        const terms = normalize(search.value.trim()).split(/\s+/).filter(Boolean);
        let matches = 0;
        options.forEach(option => {
            const text = normalize(option.textContent);
            const matched = terms.every(term => text.includes(term));
            option.hidden = !matched || ++matches > limit;
        });
        picker.querySelector('[data-result-count]').textContent = `${matches} matéria(s) encontrada(s). Exibindo ${Math.min(matches, limit)}.`;
        picker.querySelector('[data-no-articles]').hidden = matches !== 0;
        more.hidden = matches <= limit;
    };
    picker.querySelector('[data-article-search-box]').hidden = false;
    search.addEventListener('input', () => { limit = 8; filter(); });
    more.addEventListener('click', () => { limit += 8; filter(); });
    picker.addEventListener('change', updateSelection);
    // Show the selected radio when browser validation needs to focus it.
    picker.addEventListener('invalid', () => { search.value = ''; limit = options.length; filter(); }, true);
    updateSelection();
    filter();
})();
