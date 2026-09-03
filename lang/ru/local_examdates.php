<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Russian language strings for local_examdates.
 *
 * @package    local_examdates
 * @copyright  2026 Ильдар
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Действия';
$string['activity'] = 'Элемент курса';
$string['activity_idnumber_required'] = 'Укажите ID number хотя бы одного элемента (теста или задания)';
$string['apply'] = 'Применить изменения';
$string['apply_complete_subject'] = 'Обновление дат элементов контроля завершено';
$string['apply_queued'] = 'Изменение поставлено в очередь и будет применено в фоновом режиме. Вы получите уведомление по завершении — результат также будет виден в истории изменений.';
$string['arrow'] = '→';
$string['assign_idnumber'] = 'ID number задания';
$string['assign_idnumber_help'] = 'ID number элемента «Задание», который нужно обновлять в каждом курсе. Оставьте поле пустым, если для этого периода нужно менять только тест. Дата начала применяется к «Разрешить ответы с», а дата окончания — к «Сроку сдачи». Если включённые «Последний срок сдачи» или «Напомнить об оценивании» окажутся раньше нового срока сдачи, они будут перенесены на новый срок сдачи.';
$string['assignment'] = 'Задание';
$string['back'] = 'Назад';
$string['cancel'] = 'Отмена';
$string['category'] = 'Категория курсов';
$string['changed_at'] = 'Когда изменено';
$string['changed_by'] = 'Кто изменил';
$string['changes_applied_detailed'] = 'Изменения успешно применены: {$a->items} элементов в {$a->courses} курсах';
$string['cli_dryrun'] = 'Режим предпросмотра (изменения не будут сохранены)';
$string['cli_error_category'] = 'Категория с ID {$a} не найдена';
$string['cli_success'] = 'Обновлено {$a->updated} элементов из {$a->total}';
$string['cli_usage'] = 'Использование: php update_exam_dates.php --categoryid=ID --examopen="YYYY-MM-DD HH:MM" --examclose="..." [--examid=exam] [--examassignid=ID] [--resit1open=...] [--resit1assignid=ID] [--resit2open=...] [--resit2assignid=ID] [--dryrun]';
$string['confirm_apply_text'] = 'Будет изменено <strong>{$a->items}</strong> элементов в <strong>{$a->courses}</strong> курсах. Продолжить?';
$string['confirm_apply_text_paged'] = 'Для производительности предпросмотр разбит на страницы. При применении будут обработаны все <strong>{$a->total}</strong> курсов в выбранной области ограниченными фоновыми пакетами. Продолжить?';
$string['confirm_apply_title'] = 'Подтверждение применения изменений';
$string['course'] = 'Курс';
$string['course_deleted'] = 'Курс удалён';
$string['dateclose'] = 'Дата и время окончания / срока сдачи';
$string['dateopen'] = 'Дата и время начала';
$string['default_category'] = 'Категория по умолчанию';
$string['default_category_desc'] = 'Категория, которая будет выбрана по умолчанию при открытии страницы управления';
$string['enable_logging'] = 'Включить логирование';
$string['enable_logging_desc'] = 'Записывать все изменения дат в журнал (рекомендуется). Отключение также отключает историю и откат.';
$string['error_activitydeleted'] = 'Невозможно откатить: элемент курса больше не существует';
$string['error_coursedeleted'] = 'Невозможно откатить: курс больше не существует';
$string['error_lognotfound'] = 'Запись журнала не найдена';
$string['error_nopermission'] = 'У вас нет прав для управления датами элементов контроля в этой категории';
$string['error_nopermission_preview'] = 'У вас нет прав для предпросмотра дат элементов контроля';
$string['error_quizdeleted'] = 'Невозможно откатить: элемент курса больше не существует';
$string['errors'] = 'Отсутствующие элементы / ошибки';
$string['event_dates_updated'] = 'Обновлены даты элемента контроля';
$string['exam'] = 'Экзамен';
$string['examdates'] = 'Даты элементов контроля';
$string['examdates:bulkupdate'] = 'Массово изменять даты элементов контроля через CLI';
$string['examdates:manage'] = 'Управлять датами элементов контроля';
$string['examdates:preview'] = 'Просматривать изменения дат элементов контроля';
$string['export_csv'] = 'Экспорт в CSV';
$string['filter_course'] = 'Фильтр по курсу';
$string['filter_date_from'] = 'Период с';
$string['filter_date_to'] = 'Период по';
$string['filter_idnumber'] = 'ID number элемента';
$string['filter_user'] = 'Фильтр по пользователю';
$string['found_assignments'] = 'Найдено заданий';
$string['found_quizzes'] = 'Найдено тестов';
$string['go_to_manage'] = 'Перейти к управлению';
$string['history_empty'] = 'История изменений пуста';
$string['history_title'] = 'История изменений дат элементов контроля';
$string['idnumber'] = 'Идентификатор (ID number)';
$string['idnumber_help'] = 'ID number элемента курса, используемый для его идентификации внутри каждого курса.';
$string['idnumber_required'] = 'ID number обязателен для заполнения';
$string['include_subcategories'] = 'Включая подкатегории';
$string['invalid_dates'] = 'Некорректные даты: дата закрытия должна быть позже даты открытия';
$string['log_retention_days'] = 'Срок хранения логов (дней)';
$string['log_retention_days_desc'] = 'Через сколько дней автоматически удалять записи из журнала (0 - не удалять)';
$string['messageprovider:apply_complete'] = 'Уведомление о завершении массового обновления дат элементов контроля';
$string['missing_activity_idnumber'] = 'В курсе «{$a->coursename}» отсутствует элемент «{$a->activity}» с idnumber = «{$a->idnumber}»';
$string['missing_idnumber'] = 'В курсе «{$a->coursename}» отсутствует элемент с idnumber = «{$a->idnumber}»';
$string['new_dates'] = 'Новые даты';
$string['no_changes_made'] = 'Изменения не были применены';
$string['no_courses_found'] = 'В выбранной категории не найдено курсов';
$string['no_limit'] = 'без ограничений';
$string['nochanges'] = 'Без изменений';
$string['not_selected'] = 'не выбрано';
$string['notfound'] = 'Не найдено';
$string['old_dates'] = 'Старые даты';
$string['pluginname'] = 'Управление датами экзаменов и заданий';
$string['preview'] = 'Предпросмотр';
$string['preview_activity_summary'] = 'Будет изменено: тестов — {$a->quizzes}, заданий — {$a->assignments}. Отсутствующие элементы / ошибки: {$a->errors}.';
$string['preview_expired'] = 'Срок действия этого предпросмотра истёк. Отправьте форму повторно, чтобы создать новый предпросмотр.';
$string['preview_heading'] = 'Предпросмотр дат элементов контроля: {$a}';
$string['preview_menu'] = 'Предпросмотр дат элементов контроля';
$string['preview_page_stats_message'] = 'Текущая страница: будет изменено {$a->items} элементов (тестов — {$a->quizzes}, заданий — {$a->assignments}) в {$a->courses} курсах; отсутствующих/ошибок — {$a->errors}. Показано {$a->shown} из {$a->total} курсов.';
$string['preview_readonly_notice'] = 'Вы можете просматривать изменения дат элементов контроля для этой категории, но не можете применять их. Для внесения изменений обратитесь к менеджеру категории.';
$string['preview_stats'] = 'Статистика предпросмотра';
$string['preview_stats_message'] = 'Будет изменено: {$a->items} элементов (тестов — {$a->quizzes}, заданий — {$a->assignments}) в {$a->courses} курсах. Отсутствующие элементы / ошибки: {$a->errors}.';
$string['privacy:metadata:local_examdates_log'] = 'Журнал изменений дат элементов контроля, выполненных пользователями.';
$string['privacy:metadata:local_examdates_log:activity_name'] = 'Название элемента, даты которого были изменены.';
$string['privacy:metadata:local_examdates_log:courseid'] = 'ID курса, в котором был изменён элемент.';
$string['privacy:metadata:local_examdates_log:extra_data'] = 'Специфичные для модуля значения дат, сохранённые для безопасного отката.';
$string['privacy:metadata:local_examdates_log:instanceid'] = 'ID экземпляра модуля элемента, даты которого были изменены.';
$string['privacy:metadata:local_examdates_log:ip_address'] = 'IP-адрес, с которого было выполнено изменение.';
$string['privacy:metadata:local_examdates_log:modulename'] = 'Тип модуля элемента, даты которого были изменены.';
$string['privacy:metadata:local_examdates_log:quizid'] = 'Устаревшее поле, содержащее ID экземпляра теста для изменений тестов.';
$string['privacy:metadata:local_examdates_log:timecreated'] = 'Время выполнения изменения.';
$string['privacy:metadata:local_examdates_log:userid'] = 'ID пользователя, выполнившего изменение.';
$string['quiz'] = 'Тест';
$string['quiz_idnumber'] = 'ID number теста';
$string['quiz_idnumber_help'] = 'ID number элемента «Тест», который нужно обновлять в каждом курсе. Оставьте поле пустым, если для этого периода нужно менять только задание.';
$string['records_total'] = 'Всего записей: {$a}';
$string['reset_filters'] = 'Сбросить фильтры';
$string['resit1'] = 'Пересдача 1';
$string['resit2'] = 'Пересдача 2';
$string['rollback'] = 'Откатить';
$string['rollback_confirm'] = 'Вы уверены, что хотите откатить изменения?';
$string['rollback_error'] = 'Ошибка отката для «{$a->activityname}» в курсе «{$a->coursename}»';
$string['rollback_notice'] = 'Откат возможен только для последнего изменения каждого элемента';
$string['rollback_success'] = 'Успешно выполнен откат для «{$a->activityname}» в курсе «{$a->coursename}»';
$string['select_at_least_one'] = 'Выберите хотя бы один период для обновления';
$string['settings'] = 'Настройки';
$string['show_filters'] = 'Показать фильтры';
$string['task_apply_updates'] = 'Применение массовых изменений дат элементов контроля';
$string['task_clean_logs'] = 'Очистка старых записей журнала дат элементов контроля';
$string['update_exam_dates'] = 'Обновить даты экзаменационного периода';
$string['update_resit1_dates'] = 'Изменить сроки пересдачи 1';
$string['update_resit2_dates'] = 'Изменить сроки пересдачи 2';
$string['view_history'] = 'Просмотр истории изменений';
