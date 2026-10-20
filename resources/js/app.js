// Rescue form: add/remove participant rows and suggest known people while typing.
document.addEventListener('click', (e) => {
    const add = e.target.closest('[data-add-participant]');
    if (add) {
        const list = document.querySelector('[data-participants]');
        const index = Date.now();
        list.insertAdjacentHTML('beforeend', document.querySelector('#participant-row').innerHTML.replaceAll('__i__', index));
        list.lastElementChild.querySelector('input').focus();
    }

    const remove = e.target.closest('[data-remove-participant]');
    if (remove && document.querySelectorAll('[data-participant]').length > 1) {
        remove.closest('[data-participant]').remove();
    }
});

let timer;
document.addEventListener('input', (e) => {
    const input = e.target.closest('[data-people-search]');
    if (!input) return;

    clearTimeout(timer);
    timer = setTimeout(async () => {
        const response = await fetch(`${input.dataset.peopleSearch}?q=${encodeURIComponent(input.value)}`, {
            headers: { Accept: 'application/json' },
        });
        const people = response.ok ? await response.json() : [];
        document.querySelector('#people').replaceChildren(
            ...people.map(({ value, label }) => Object.assign(document.createElement('option'), { value, label })),
        );
    }, 200);
});

// Reject oversized photos before uploading them (the server checks again).
document.addEventListener('change', (e) => {
    const input = e.target.closest('[data-max-bytes]');
    if (!input) return;

    const tooLarge = [...input.files].some((file) => file.size > Number(input.dataset.maxBytes));
    input.setCustomValidity(tooLarge ? input.dataset.tooLarge : '');
    input.reportValidity();
});

// Dark mode toggle: a click saves the visitor's choice; without one, the site follows the system setting.
const systemDark = matchMedia('(prefers-color-scheme: dark)');
const savedTheme = () => {
    try {
        return localStorage.getItem('theme');
    } catch {
        return null;
    }
};
const applyTheme = (dark) => {
    document.documentElement.classList.toggle('dark', dark);
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => button.setAttribute('aria-pressed', String(dark)));
};

applyTheme(document.documentElement.classList.contains('dark'));
systemDark.addEventListener('change', (e) => savedTheme() || applyTheme(e.matches));

document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-theme-toggle]')) return;

    const dark = !document.documentElement.classList.contains('dark');
    applyTheme(dark);
    try {
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    } catch {}
});
