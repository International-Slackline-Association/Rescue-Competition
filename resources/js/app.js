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
