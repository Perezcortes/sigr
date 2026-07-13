<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" data-agenda-fc-css />
<style data-agenda-fc-style>{!! file_get_contents(resource_path('css/agenda-calendar.css')) !!}</style>

<script>
    if (! window.__agendaCalendarBootstrapped) {
        window.__agendaCalendarBootstrapped = true;

        window.agendaCalendarLoadAssets = function (done) {
            if (typeof FullCalendar !== 'undefined') {
                done();
                return;
            }

            const loadLocale = () => {
                if (document.querySelector('script[data-agenda-fc-locale]')) {
                    done();
                    return;
                }
                const locale = document.createElement('script');
                locale.src = 'https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js';
                locale.dataset.agendaFcLocale = '1';
                locale.onload = done;
                locale.onerror = () => done();
                document.head.appendChild(locale);
            };

            if (document.querySelector('script[data-agenda-fc-core]')) {
                const wait = setInterval(() => {
                    if (typeof FullCalendar !== 'undefined') {
                        clearInterval(wait);
                        loadLocale();
                    }
                }, 50);
                return;
            }

            const core = document.createElement('script');
            core.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js';
            core.dataset.agendaFcCore = '1';
            core.onload = loadLocale;
            core.onerror = () => {
                const err = document.getElementById('agenda-calendar-error');
                if (err) {
                    err.textContent = 'No se pudo cargar el calendario. Revisa tu conexión o desactiva bloqueadores de scripts.';
                    err.classList.remove('hidden');
                }
            };
            document.head.appendChild(core);
        };

        window.agendaCalendarMount = function () {
            const root = document.getElementById('agenda-calendar-root');
            const el = document.getElementById('agenda-fullcalendar');
            const err = document.getElementById('agenda-calendar-error');
            const empty = document.getElementById('agenda-calendar-empty');

            if (! root || ! el) {
                return;
            }

            if (root.closest('.hidden') !== null || root.classList.contains('hidden')) {
                return;
            }

            if (typeof FullCalendar === 'undefined') {
                if (err) {
                    err.textContent = 'Cargando calendario…';
                    err.classList.remove('hidden');
                }
                window.agendaCalendarLoadAssets(() => window.agendaCalendarMount());
                return;
            }

            if (err) {
                err.classList.add('hidden');
            }

            let eventos = [];
            const dataEl = document.getElementById('agenda-events-json');
            if (dataEl) {
                try {
                    eventos = JSON.parse(dataEl.textContent || '[]');
                } catch (e) {
                    eventos = [];
                }
            }

            if (eventos.length === 0) {
                if (empty) empty.classList.remove('hidden');
                el.classList.add('hidden');
                if (el._fcCalendar) {
                    el._fcCalendar.destroy();
                    el._fcCalendar = null;
                }
                return;
            }

            if (empty) empty.classList.add('hidden');
            el.classList.remove('hidden');

            const isMobile = window.matchMedia('(max-width: 768px)').matches;
            const tooltip = document.getElementById('agenda-cal-tooltip');

            if (el._fcCalendar) {
                el._fcCalendar.destroy();
                el._fcCalendar = null;
            }

            el._fcCalendar = new FullCalendar.Calendar(el, {
                locale: 'es',
                timeZone: 'local',
                initialView: isMobile ? 'listWeek' : 'dayGridMonth',
                headerToolbar: isMobile
                    ? { left: 'prev,next', center: 'title', right: 'today' }
                    : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día',
                    list: 'Agenda',
                },
                height: isMobile ? 'auto' : 680,
                expandRows: true,
                stickyHeaderDates: true,
                dayMaxEvents: isMobile ? false : 4,
                moreLinkClick: 'popover',
                navLinks: true,
                nowIndicator: true,
                slotMinTime: '07:00:00',
                slotMaxTime: '21:00:00',
                allDayText: 'Todo el día',
                events: eventos,
                eventDisplay: 'block',
                displayEventTime: true,
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                eventClick(info) {
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.open(info.event.url, '_blank');
                    }
                },
                eventMouseEnter(info) {
                    if (! tooltip) return;
                    const p = info.event.extendedProps;
                    const partes = [
                        '<p class="font-semibold text-gray-900 dark:text-white">' + (p.lead || '') + '</p>',
                        p.hora ? '<p class="text-xs font-medium mt-0.5" style="color:#26cad3">' + p.hora + '</p>' : '',
                        '<p class="text-gray-600 dark:text-gray-300 mt-1.5 leading-snug">' + (p.descripcion || '') + '</p>',
                    ];
                    if (p.agente) {
                        partes.push('<p class="text-xs text-gray-500 mt-1">Agente: ' + p.agente + '</p>');
                    }
                    const estado = p.completada ? 'Realizada' : (p.vencida ? 'Vencida' : 'Pendiente');
                    partes.push('<p class="text-xs mt-2 font-semibold">' + estado + '</p>');
                    tooltip.innerHTML = partes.join('');
                    tooltip.classList.remove('hidden');
                    tooltip.style.left = Math.min(info.jsEvent.pageX + 12, window.innerWidth - 280) + 'px';
                    tooltip.style.top = Math.min(info.jsEvent.pageY + 12, window.innerHeight - 120) + 'px';
                },
                eventMouseLeave() {
                    if (tooltip) tooltip.classList.add('hidden');
                },
            });

            el._fcCalendar.render();
        };

        function agendaCalendarScheduleMount() {
            window.agendaCalendarLoadAssets(() => {
                setTimeout(window.agendaCalendarMount, 30);
            });
        }

        document.addEventListener('DOMContentLoaded', agendaCalendarScheduleMount);
        document.addEventListener('livewire:init', () => {
            Livewire.on('agenda-calendar-refresh', () => {
                setTimeout(agendaCalendarScheduleMount, 80);
            });

            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    queueMicrotask(agendaCalendarScheduleMount);
                });
            });
        });
        document.addEventListener('livewire:navigated', agendaCalendarScheduleMount);
    }
</script>
