const view = document.querySelector('#view');
const dialog = document.querySelector('#projectDialog');
const form = document.querySelector('#projectForm');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const newProjectButton = document.querySelector('#newProject');
const sidebar = document.querySelector('#sidebar');
const shade = document.querySelector('#shade');
const toastElement = document.querySelector('#toast');

let people = [];
let projects = [];
let user = null;
let currentView = 'overview';
let busy = false;
let toastTimer;

const statuses = {
    nowe: 'Zaplanowane',
    'w toku': 'W realizacji',
    zakończone: 'Zakończone',
    anulowane: 'Anulowane',
};

const pages = {
    overview: [
        'Przegląd',
        'Wszystko pod kontrolą.',
        'Dobry dzień na kolejny krok. Oto, co dzieje się w Twojej firmie.',
    ],
    projects: [
        'Projekty',
        'Od pomysłu do efektu.',
        'Zlecenia, terminy i postępy w jednym miejscu.',
    ],
    team: ['Zespół', 'Ludzie robią różnicę.', 'Zespół, który zamienia plany w rezultaty.'],
    schedule: [
        'Harmonogram',
        'Miejsce na dobry plan.',
        'Terminy projektów i odpowiedzialne osoby.',
    ],
    reports: ['Raporty', 'Zobacz szerszy obraz.', 'Aktualne wyniki i postępy Twojego zespołu.'],
    settings: [
        'Ustawienia',
        'Twoja przestrzeń pracy.',
        'Informacje o koncie i połączeniu z bazą danych.',
    ],
};

function escape(value) {
    const characters = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    };

    return String(value).replace(/[&<>"']/g, (character) => characters[character]);
}

function formatDate(value) {
    return new Date(`${value}T12:00:00`).toLocaleDateString('pl-PL', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function toast(message) {
    clearTimeout(toastTimer);
    toastElement.textContent = message;
    toastElement.classList.add('show');
    toastTimer = setTimeout(() => toastElement.classList.remove('show'), 4000);
}

async function request(payload = null) {
    const options = {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    };

    if (payload) {
        options.method = 'POST';
        options.headers['Content-Type'] = 'application/json';
        options.headers['X-CSRF-Token'] = csrfToken;
        options.body = JSON.stringify(payload);
    }

    const response = await fetch('api.php', options);
    const data = await response.json();

    if (response.status === 401) {
        window.location.assign('login.php');
        throw new Error('Sesja wygasła.');
    }

    if (!response.ok) {
        throw new Error(data.error || 'Nie udało się pobrać danych.');
    }

    return data;
}

async function loadData() {
    const data = await request();
    user = data.user;
    people = data.employees.map((employee) => ({
        id: Number(employee.id),
        name: `${employee.first_name} ${employee.last_name}`.trim(),
        role: employee.employee_type,
        initials:
            `${employee.first_name.slice(0, 1)}${employee.last_name.slice(0, 1)}`.toUpperCase(),
    }));

    projects = data.projects.map((project) => {
        const assigned = JSON.parse(project.employees || '[]');
        const ownerId = Number(project.owner_id || assigned[0]);
        const owner = people.find((person) => person.id === ownerId);
        const fallbackProgress =
            project.status === 'zakończone' ? 100 : project.status === 'w toku' ? 25 : 0;

        return {
            id: Number(project.id),
            name: project.name,
            number: project.order_number,
            client: project.client,
            location: project.location,
            date: project.order_date,
            ownerId,
            owner: owner?.name || 'Nieprzypisany',
            progress: project.progress === null ? fallbackProgress : Number(project.progress),
            status: statuses[project.status],
        };
    });

    newProjectButton.hidden = user.role !== 'admin';
    document.querySelector('#ownerSelect').innerHTML = people
        .map((person) => `<option value="${person.id}">${escape(person.name)}</option>`)
        .join('');
}

function badge(project) {
    const className =
        project.status === 'Zakończone'
            ? 'done'
            : project.status === 'W realizacji'
              ? 'active'
              : project.status === 'Anulowane'
                ? 'cancelled'
                : '';

    return `<span class="tag ${className}">${escape(project.status)}</span>`;
}

function statistics() {
    const active = projects.filter((project) => project.status === 'W realizacji').length;
    const completed = projects.filter((project) => project.status === 'Zakończone').length;
    const average = Math.round(
        projects.reduce((total, project) => total + project.progress, 0) / (projects.length || 1),
    );
    const items = [
        ['Aktywne projekty', active, '◫', 'W trakcie realizacji'],
        ['Zespół', people.length, '♧', 'Pracownicy firmy'],
        ['Zakończone projekty', completed, '✓', 'Zrealizowane zlecenia'],
        ['Średni postęp', `${average}%`, '↗', 'Dla widocznych projektów'],
    ];

    return `
        <div class="stats">
            ${items
                .map(
                    ([label, value, icon, note]) => `
                        <div class="stat">
                            <div class="stat-top">
                                ${label}
                                <span class="stat-icon">${icon}</span>
                            </div>
                            <div class="stat-value">${value}</div>
                            <small>${note}</small>
                        </div>
                    `,
                )
                .join('')}
        </div>
    `;
}

function projectActions(project) {
    if (user.role !== 'admin') {
        return '';
    }

    const disabled = project.progress === 100 || project.status === 'Anulowane';

    return `
        <td>
            <button class="text-button" data-advance="${project.id}" ${disabled ? 'disabled' : ''}>
                ${project.progress === 100 ? 'Gotowe' : '＋ Postęp'}
            </button>
            <button
                class="text-button"
                data-delete="${project.id}"
                aria-label="Usuń projekt ${escape(project.name)}"
            >
                ×
            </button>
        </td>
    `;
}

function projectTable(list, editable = false) {
    const canEdit = editable && user.role === 'admin';

    return `
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Projekt / klient</th>
                        <th>Status</th>
                        <th>Termin</th>
                        <th>Postęp</th>
                        ${canEdit ? '<th>Akcje</th>' : ''}
                    </tr>
                </thead>
                <tbody>
                    ${list
                        .map(
                            (project) => `
                                <tr>
                                    <td>
                                        <b>${escape(project.name)}</b>
                                        <small>${escape(project.client)} · ${escape(project.owner)}</small>
                                        <small>${escape(project.location)}</small>
                                    </td>
                                    <td>${badge(project)}</td>
                                    <td>${formatDate(project.date)}</td>
                                    <td>
                                        <div class="project-progress">
                                            ${project.progress}%
                                            <div class="progress">
                                                <i style="width: ${project.progress}%"></i>
                                            </div>
                                        </div>
                                    </td>
                                    ${canEdit ? projectActions(project) : ''}
                                </tr>
                            `,
                        )
                        .join('')}
                </tbody>
            </table>
            ${list.length ? '' : '<div class="empty">Brak projektów do wyświetlenia.</div>'}
        </div>
    `;
}

function statusChart() {
    const labels = ['Zaplanowane', 'W realizacji', 'Zakończone', 'Anulowane'];
    const counts = labels.map(
        (status) => projects.filter((project) => project.status === status).length,
    );

    return `
        <div class="panel">
            <div class="panel-head">
                <h2>Projekty według statusu</h2>
                <span class="tag">Aktualne dane</span>
            </div>
            <div class="bars">
                ${counts
                    .map(
                        (count, index) => `
                            <div class="bar-col">
                                <span>${count}</span>
                                <i style="height: ${Math.max(4, (count / Math.max(1, ...counts)) * 100)}px"></i>
                                <span>${labels[index]}</span>
                            </div>
                        `,
                    )
                    .join('')}
            </div>
        </div>
    `;
}

function overview() {
    return `
        ${statistics()}
        <div class="overview-grid">
            <div>
                <div class="panel">
                    <div class="panel-head">
                        <h2>Twoje projekty</h2>
                        <button class="text-button" data-view="projects">Wszystkie projekty ↗</button>
                    </div>
                    ${projectTable(projects.slice(0, 4))}
                </div>
                ${statusChart()}
            </div>
            <div>
                <div class="panel focus">
                    <span class="eyebrow">DOBRY PLAN TO POCZĄTEK</span>
                    <h2>Wielkie rzeczy.<br>Małe kroki każdego dnia.</h2>
                    <p>Sprawdź nadchodzące terminy i zaplanuj pracę swojego zespołu.</p>
                    <button class="button" data-view="schedule">Zobacz harmonogram ↗</button>
                </div>
                <div class="panel">
                    <div class="panel-head">
                        <h2>Osoby prowadzące</h2>
                        <span class="tag">Projekty: ${projects.length}</span>
                    </div>
                    ${projects
                        .slice(0, 3)
                        .map(
                            (project) => `
                                <div class="activity">
                                    <span class="avatar">
                                        ${escape(people.find((person) => person.id === project.ownerId)?.initials || '—')}
                                    </span>
                                    <div>
                                        <b>${escape(project.owner)}</b><br>
                                        ${escape(project.name)}<br>
                                        <small>Termin: ${formatDate(project.date)}</small>
                                    </div>
                                </div>
                            `,
                        )
                        .join('')}
                    ${projects.length ? '' : '<p class="empty">Brak przypisanych projektów.</p>'}
                </div>
            </div>
        </div>
    `;
}

function projectList() {
    return `
        <div class="panel">
            <div class="toolbar">
                <input
                    type="search"
                    id="search"
                    aria-label="Szukaj projektu"
                    placeholder="Szukaj projektu, klienta lub osoby…"
                >
                <select id="statusFilter" aria-label="Status projektu">
                    <option value="">Wszystkie statusy</option>
                    ${Object.values(statuses)
                        .map((status) => `<option>${status}</option>`)
                        .join('')}
                </select>
            </div>
            <div id="projectTable">${projectTable(projects, true)}</div>
        </div>
    `;
}

function team() {
    return `
        <div class="team-grid">
            ${people
                .map(
                    (person) => `
                        <div class="panel member">
                            <span class="avatar">${escape(person.initials)}</span>
                            <h3>${escape(person.name)}</h3>
                            <p>${escape(person.role)}</p>
                            <span class="tag active">
                                Projekty: ${projects.filter((project) => project.ownerId === person.id).length}
                            </span>
                        </div>
                    `,
                )
                .join('')}
        </div>
    `;
}

function schedule() {
    const sorted = [...projects].sort((first, second) => first.date.localeCompare(second.date));

    return `
        <div class="panel">
            <div class="panel-head">
                <h2>Terminy projektów</h2>
                <span class="tag">Terminy: ${projects.length}</span>
            </div>
            ${sorted
                .map(
                    (project) => `
                        <div class="schedule-row">
                            <div class="day">
                                <b>${new Date(`${project.date}T12:00:00`).getDate()}</b>
                                ${new Date(`${project.date}T12:00:00`).toLocaleDateString('pl-PL', { month: 'short' })}
                            </div>
                            <div>
                                <b>${escape(project.name)}</b>
                                <p>${escape(project.client)} · ${escape(project.owner)}</p>
                            </div>
                            ${badge(project)}
                        </div>
                    `,
                )
                .join('')}
            ${projects.length ? '' : '<p class="empty">Nie ma jeszcze zaplanowanych terminów.</p>'}
        </div>
    `;
}

function settings() {
    return `
        <div class="panel settings-panel">
            <h2>Twoje konto</h2>
            <dl class="account-details">
                <dt>Imię i nazwisko</dt>
                <dd>${escape(`${user.first_name} ${user.last_name}`)}</dd>
                <dt>Login</dt>
                <dd>${escape(user.login)}</dd>
                <dt>Uprawnienia</dt>
                <dd>${user.role === 'admin' ? 'Administrator' : 'Pracownik'}</dd>
                <dt>Baza danych</dt>
                <dd>MySQL · Połączono</dd>
            </dl>
            <p class="settings-description">
                Projekty i dane zespołu są przechowywane w firmowej bazie danych.
                Administrator może dodawać projekty, aktualizować postęp i usuwać zlecenia.
            </p>
        </div>
    `;
}

function filterProjects() {
    const phrase = document.querySelector('#search').value.toLocaleLowerCase('pl');
    const status = document.querySelector('#statusFilter').value;
    const filtered = projects.filter((project) => {
        const text = `${project.name} ${project.client} ${project.owner}`.toLocaleLowerCase('pl');
        return text.includes(phrase) && (!status || project.status === status);
    });

    document.querySelector('#projectTable').innerHTML = projectTable(filtered, true);
}

function render(name = currentView) {
    currentView = name;
    const [label, heading, subtitle] = pages[name];
    document.querySelector('#breadcrumb').textContent = label;
    document.querySelector('#heading').textContent = heading;
    document.querySelector('#subtitle').textContent = subtitle;
    document.querySelector('#projectCount').textContent = projects.length;
    document.querySelectorAll('nav button').forEach((button) => {
        button.classList.toggle('active', button.dataset.view === name);
    });
    sidebar.classList.remove('open');
    shade.classList.remove('show');

    const templates = {
        overview,
        projects: projectList,
        team,
        schedule,
        reports: () => `
            ${statistics()}
            ${statusChart()}
            <div class="panel">
                <div class="panel-head"><h2>Postępy projektów</h2></div>
                ${projectTable(projects)}
            </div>
        `,
        settings,
    };

    view.innerHTML = templates[name]();

    if (name === 'projects') {
        document.querySelector('#search').addEventListener('input', filterProjects);
        document.querySelector('#statusFilter').addEventListener('change', filterProjects);
    }
}

async function mutate(payload) {
    if (busy) {
        return;
    }

    busy = true;
    view.setAttribute('aria-busy', 'true');

    try {
        const result = await request(payload);
        await loadData();
        render();
        toast(result.message);
    } catch (error) {
        toast(error.message);
    } finally {
        busy = false;
        view.removeAttribute('aria-busy');
    }
}

newProjectButton.addEventListener('click', () => {
    dialog.showModal();
    form.querySelector('input').focus();
});

document.querySelector('#closeDialog').addEventListener('click', () => dialog.close());

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (busy) {
        return;
    }

    busy = true;
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    const fields = new FormData(form);

    try {
        const result = await request({
            action: 'create',
            name: fields.get('name'),
            client: fields.get('client'),
            location: fields.get('location'),
            date: fields.get('date'),
            ownerId: Number(fields.get('ownerId')),
        });
        form.reset();
        dialog.close();
        await loadData();
        render('projects');
        toast(result.message);
    } catch (error) {
        toast(error.message);
    } finally {
        busy = false;
        submitButton.disabled = false;
    }
});

document.addEventListener('click', async (event) => {
    const navigation = event.target.closest('[data-view]');

    if (navigation && user) {
        render(navigation.dataset.view);
    }

    const advance = event.target.closest('[data-advance]');

    if (advance && !advance.disabled) {
        await mutate({ action: 'advance', id: Number(advance.dataset.advance) });
    }

    const remove = event.target.closest('[data-delete]');

    if (remove && !busy && confirm('Usunąć projekt z bazy danych?')) {
        await mutate({ action: 'delete', id: Number(remove.dataset.delete) });
    }
});

document.querySelector('#menu').addEventListener('click', () => {
    sidebar.classList.toggle('open');
    shade.classList.toggle('show');
});

shade.addEventListener('click', () => {
    sidebar.classList.remove('open');
    shade.classList.remove('show');
});

document.querySelector('#date').textContent = new Date()
    .toLocaleDateString('pl-PL', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    })
    .toUpperCase();

loadData()
    .then(() => render())
    .catch((error) => {
        view.innerHTML = `
            <div class="panel">
                <h2>Nie udało się załadować danych</h2>
                <p class="settings-description">${escape(error.message)}</p>
                <button class="button" id="retry">Spróbuj ponownie</button>
            </div>
        `;
        document.querySelector('#retry').addEventListener('click', () => window.location.reload());
    });
