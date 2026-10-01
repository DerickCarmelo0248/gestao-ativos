document.querySelectorAll('select[name="item_id"]').forEach((select, index) => {
    const options = Array.from(select.options).filter(option => option.value && !option.disabled);
    if (options.length < 2 || select.disabled) return;
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim();
    const textOf = option => option.textContent.replace(/\s+/g, ' ').trim();
    const wrapper = document.createElement('span');
    wrapper.style.cssText = 'display:block;position:relative;width:100%;';
    const input = document.createElement('input');
    input.type = 'text';
    input.id = `item-picker-${index}`;
    input.placeholder = 'Pesquisar por nome ou código…';
    input.autocomplete = 'off';
    input.required = select.required;
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    const list = document.createElement('span');
    list.id = `${input.id}-list`;
    list.setAttribute('role', 'listbox');
    list.style.cssText = 'position:absolute;top:100%;left:0;right:0;z-index:100;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 8px 20px #0002;max-height:260px;overflow-y:auto;';
    list.hidden = true;
    input.setAttribute('aria-controls', list.id);
    Array.from(select.labels).forEach(label => { label.htmlFor = input.id; });
    let matches = [], active = -1;
    function close() {
        list.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
    }
    function sync() {
        input.value = textOf(options.find(option => option.value === select.value) || { textContent: '' });
        input.setCustomValidity('');
    }
    function choose(option) {
        select.value = option.value;
        sync();
        close();
        select.dispatchEvent(new Event('change', { bubbles: true }));
        input.focus();
        close();
    }
    function highlight() {
        Array.from(list.children).forEach((row, i) => {
            row.style.background = i === active ? '#eef2f7' : '#fff';
            row.setAttribute('aria-selected', String(i === active));
        });
        if (active >= 0) {
            input.setAttribute('aria-activedescendant', list.children[active].id);
            list.children[active].scrollIntoView({ block: 'nearest' });
        }
    }
    function render(all = false) {
        const terms = all ? [] : normalize(input.value).split(/\s+/).filter(Boolean);
        matches = options.filter(option => terms.every(term => normalize(textOf(option)).includes(term)));
        active = -1;
        list.replaceChildren();
        input.removeAttribute('aria-activedescendant');
        matches.forEach((option, i) => {
            const row = document.createElement('span');
            row.id = `${input.id}-option-${i}`;
            row.setAttribute('role', 'option');
            row.setAttribute('aria-selected', 'false');
            row.style.cssText = 'display:block;padding:10px 12px;cursor:pointer;line-height:1.4;border-bottom:1px solid #f1f5f9;';
            row.textContent = textOf(option);
            row.addEventListener('mousedown', event => event.preventDefault());
            row.addEventListener('click', () => choose(option));
            row.addEventListener('mouseenter', () => { active = i; highlight(); });
            list.append(row);
        });
        if (!matches.length) {
            const empty = document.createElement('span');
            empty.textContent = 'Nenhum item encontrado.';
            empty.style.cssText = 'display:block;padding:12px;color:#64748b;';
            list.append(empty);
        }
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }
    input.addEventListener('focus', () => render(Boolean(select.value)));
    input.addEventListener('click', () => { if (list.hidden) render(Boolean(select.value)); });
    input.addEventListener('input', () => {
        const previous = select.value;
        select.value = '';
        input.setCustomValidity(input.value ? 'Selecione um item nas sugestões.' : '');
        if (previous) select.dispatchEvent(new Event('change', { bubbles: true }));
        render();
    });
    input.addEventListener('blur', () => setTimeout(close, 150));
    input.addEventListener('keydown', event => {
        if (event.key === 'Escape') { close(); return; }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (list.hidden) render(Boolean(select.value));
            if (matches.length) {
                active = event.key === 'ArrowDown' ? (active + 1) % matches.length : (active <= 0 ? matches.length - 1 : active - 1);
                highlight();
            }
        }
        if (event.key === 'Enter' && !list.hidden) {
            event.preventDefault();
            if (active >= 0) choose(matches[active]);
            else if (matches.length === 1) choose(matches[0]);
        }
    });
    sync();
    wrapper.append(input, list);
    select.before(wrapper);
    select.hidden = true;
    select.style.display = 'none';
    select.required = false;
    select.form?.addEventListener('reset', () => setTimeout(() => { sync(); close(); }, 0));
});