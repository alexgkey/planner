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

        allDayText: '',

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
            const props = arg.event.extendedProps;
            let title = arg.event.title;

            let titleClasses = 'fc-event-title';
            if (props.isCancelled) {
                titleClasses += ' text-decoration-line-through';
            }

            const departmentHtml = props.department ? `<div style="font-size: 0.75em; color: rgba(255, 255, 255, 0.8);">${props.department}</div>` : '';

            // --- ИЗМЕНЕННАЯ ЛОГИКА ---
            // Добавляем иконку прямо в строку с названием
            if (props.isCompleted) {
                title += ` <i class="bi bi-check-circle-fill" style="font-size: 0.8em;" title="Проведено"></i>`;
            }

            const titleHtml = `<div class="${titleClasses}">${title}</div>`;

            if (arg.view.type.startsWith('list')) {
                const departmentText = props.department ? `<span class="text-muted small ms-2">(${props.department})</span>` : '';
                const completedIconListHtml = props.isCompleted ? `<i class="bi bi-check-circle-fill text-success ms-2" title="Проведено"></i>` : '';
                const cancelledIconListHtml = props.isCancelled ? `<i class="bi bi-x-circle-fill text-danger ms-2" title="Отменено"></i>` : '';
                // Для списка мы не добавляем иконку в title, а показываем отдельно
                return { html: `<div class="${titleClasses}">${arg.event.title}${departmentText}${completedIconListHtml}${cancelledIconListHtml}</div>` };
            }

            // Для сетки возвращаем новую структуру
            return { html: departmentHtml + titleHtml };
        },

        views: {
            listMonth: {
                listDayFormat: { weekday: 'long', month: 'long', day: 'numeric' },
                listDaySideFormat: false
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
