document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        return; // Если элемента календаря нет на странице, ничего не делаем
    }

    // --- ИНИЦИАЛИЗАЦИЯ КАЛЕНДАРЯ ---
    const calendar = new FullCalendar.Calendar(calendarEl, {
        // --- Основные настройки ---
        initialView: 'dayGridMonth', // Вид по умолчанию - месяц
        locale: 'ru', // Включаем русскую локализацию
        headerToolbar: false, // Мы используем свои кнопки, поэтому отключаем стандартную шапку

        // --- Источник данных ---
        events: '/api/events', // URL нашего API-эндпоинта

        // --- Внешний вид и поведение ---
        buttonText: {
            today: 'Сегодня',
        },
        eventTimeFormat: { // Формат времени для событий
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false
        },
        // Делаем события кликабельными
        eventClick: function(info) {
            info.jsEvent.preventDefault(); // Предотвращаем стандартное действие
            if (info.event.url) {
                window.open(info.event.url, "_self"); // Открываем ссылку в том же окне
            }
        },

        // --- Пользовательские виды ---
        views: {
            listQuarter: {
                type: 'list',
                duration: { months: 3 },
                buttonText: 'Квартал'
            }
        }
    });

    // Отрисовываем календарь
    calendar.render();

    // --- ОБРАБОТКА НАШИХ КНОПОК УПРАВЛЕНИЯ ---
    const controls = document.getElementById('calendar-controls');
    if (controls) {
        // Кнопки переключения вида (Месяц, Квартал, Год)
        controls.querySelectorAll('[data-view]').forEach(button => {
            button.addEventListener('click', () => {
                const viewName = button.getAttribute('data-view');
                calendar.changeView(viewName);

                // Подсветка активной кнопки
                controls.querySelectorAll('[data-view]').forEach(btn => btn.classList.remove('btn-primary'));
                controls.querySelectorAll('[data-view]').forEach(btn => btn.classList.add('btn-secondary'));
                button.classList.remove('btn-secondary');
                button.classList.add('btn-primary');
            });
        });

        // Кнопки навигации (Вперед, Назад, Сегодня)
        controls.querySelectorAll('[data-nav]').forEach(button => {
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
