document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        return;
    }

    const modalEl = document.getElementById('event-modal');
    const modal = new bootstrap.Modal(modalEl);
    const modalContent = modalEl.querySelector('.modal-content');

    const isMobile = window.innerWidth < 768;
    const initialViewName = isMobile ? 'listMonth' : 'dayGridMonth';

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: initialViewName,
        locale: 'ru',
        headerToolbar: false,
        events: '/api/events',

        // --- УБИРАЕМ НАДПИСЬ "ALL-DAY" ---
        allDayText: '', // Заменяем "all-day" на пустую строку

        buttonText: {
            today: 'Сегодня',
            month: 'Месяц',
            list:  'Список'
        },

        eventClick: function(info) {
            info.jsEvent.preventDefault();

            const eventId = info.event.id;
            if (!eventId) {
                return;
            }

            modalContent.innerHTML = '<div class="modal-body text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            modal.show();

            fetch(`/api/event/${eventId}/details`)
                .then(response => response.json())
                .then(data => {
                    modalContent.innerHTML = data.html;
                })
                .catch(error => {
                    console.error('Error fetching event details:', error);
                    modalContent.innerHTML = '<div class="modal-body">Не удалось загрузить информацию о событии.</div>';
                });
        },

        eventContent: function(arg) {
            let department = arg.event.extendedProps.department;
            let title = arg.event.title;
            let departmentHtml = department ? `<div style="font-size: 0.75em; color: rgba(255, 255, 255, 0.8);">${department}</div>` : '';
            let titleHtml = `<div class="fc-event-title">${title}</div>`;

            // Для вида "список" мы хотим другой HTML
            if (arg.view.type.startsWith('list')) {
                departmentHtml = department ? `<span class="text-muted small ms-2">(${department})</span>` : '';
                return { html: `<div class="fc-event-title">${title}${departmentHtml}</div>` };
            }

            return { html: departmentHtml + titleHtml };
        },

        views: {
            listMonth: {
                // Настраиваем формат даты для списка на месяц
                listDayFormat: { weekday: 'long', month: 'long', day: 'numeric' },
                listDaySideFormat: false // Убираем дублирование даты сбоку
            },
            listQuarter: {
                type: 'list',
                duration: { months: 3 },
                buttonText: 'Квартал',
                listDayFormat: { weekday: 'long', month: 'long', day: 'numeric' },
                listDaySideFormat: false
            },
            listYear: {
                listDayFormat: { weekday: 'short', month: 'long', day: 'numeric' },
                listDaySideFormat: false
            }
        }
    });

    calendar.render();

    const controls = document.getElementById('calendar-controls');
    if (controls) {
        const viewButtons = controls.querySelectorAll('[data-view]');
        const navButtons = controls.querySelectorAll('[data-nav]');

        const updateActiveButton = (activeView) => {
            viewButtons.forEach(btn => {
                if (btn.getAttribute('data-view') === activeView) {
                    btn.classList.remove('btn-secondary');
                    btn.classList.add('btn-primary');
                } else {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-secondary');
                }
            });
        };

        updateActiveButton(initialViewName);

        viewButtons.forEach(button => {
            button.addEventListener('click', () => {
                const viewName = button.getAttribute('data-view');
                calendar.changeView(viewName);
                updateActiveButton(viewName);
            });
        });

        navButtons.forEach(button => {
            button.addEventListener('click', () => {
                const navAction = button.getAttribute('data-nav');
                switch (navAction) {
                    case 'prev':
                        calendar.prev();
                        break;
                    case 'next':
                        calendar.next();
                        break;
                    case 'today':
                        calendar.today();
                        break;
                }
            });
        });
    }
});
