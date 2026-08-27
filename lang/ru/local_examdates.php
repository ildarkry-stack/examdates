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
$string['apply'] = 'Применить изменения';
$string['apply_complete_subject'] = 'Обновление дат экзаменов завершено';
$string['apply_queued'] = 'Изменение поставлено в очередь и будет применено в фоновом режиме. Вы получите уведомление по завершении — результат также будет виден в истории изменений.';
$string['arrow'] = '→';
$string['back'] = 'Назад';
$string['cancel'] = 'Отмена';
$string['category'] = 'Категория курсов';
$string['changed_at'] = 'Когда изменено';
$string['changed_by'] = 'Кто изменил';
$string['changes_applied_detailed'] = 'Изменения успешно применены: {$a->tests} тестов в {$a->courses} курсах';
$string['cli_dryrun'] = 'Режим предпросмотра (изменения не будут сохранены)';
$string['cli_error_category'] = 'Категория с ID {$a} не найдена';
$string['cli_success'] = 'Обновлено {$a->updated} тестов из {$a->total}';
$string['cli_usage'] = 'Использование: php update_exam_dates.php --categoryid=ID --examopen="YYYY-MM-DD HH:MM" --examclose="..." [--resit1open=...] [--resit2open=...] [--dryrun]';
$string['confirm_apply_text'] = 'Будет изменено <strong>{$a->tests}</strong> тестов в <strong>{$a->courses}</strong> курсах. Продолжить?';
$string['confirm_apply_title'] = 'Подтверждение применения изменений';
$string['course'] = 'Курс';
$string['course_deleted'] = 'Курс удалён';
$string['dateclose'] = 'Дата и время закрытия';
$string['dateopen'] = 'Дата и время открытия';
$string['default_category'] = 'Категория по умолчанию';
$string['default_category_desc'] = 'Категория, которая будет выбрана по умолчанию при открытии страницы управления';
$string['enable_logging'] = 'Включить логирование';
$string['enable_logging_desc'] = 'Записывать все изменения дат в журнал (рекомендуется). Отключение также отключает историю и откат.';
$string['error_coursedeleted'] = 'Невозможно откатить: курс больше не существует';
$string['error_lognotfound'] = 'Запись журнала не найдена';
$string['error_nopermission'] = 'У вас нет прав для управления датами экзаменов в этой категории';
$string['error_nopermission_preview'] = 'У вас нет прав для просмотра дат экзаменов';
$string['error_quizdeleted'] = 'Невозможно откатить: тест больше не существует';
$string['errors'] = 'Пропущенные тесты / ошибки';
$string['event_dates_updated'] = 'Даты экзаменов обновлены';
$string['exam'] = 'Экзамен';
$string['examdates'] = 'Даты экзаменов';
$string['examdates:bulkupdate'] = 'Массовое обновление дат экзаменов через CLI';
$string['examdates:manage'] = 'Управление датами экзаменационных тестов';
$string['examdates:preview'] = 'Просмотр изменений дат экзаменов';
$string['export_csv'] = 'Экспорт в CSV';
$string['filter_course'] = 'Фильтр по курсу';
$string['filter_date_from'] = 'Период с';
$string['filter_date_to'] = 'Период по';
$string['filter_idnumber'] = 'Тип теста';
$string['filter_user'] = 'Фильтр по пользователю';
$string['found_quizzes'] = 'Найдено тестов';
$string['go_to_manage'] = 'Перейти к управлению';
$string['history_empty'] = 'История изменений пуста';
$string['history_title'] = 'Журнал изменений дат экзаменов';
$string['idnumber'] = 'Идентификатор (ID number)';
$string['idnumber_help'] = 'Идентификационный номер (ID number) теста, который вы задали в настройках модуля "Тест". Обычно используется "exam", "resit1", "resit2", но вы можете указать любое уникальное значение.';
$string['idnumber_required'] = 'ID number обязателен для заполнения';
$string['include_subcategories'] = 'Включая подкатегории';
$string['invalid_dates'] = 'Некорректные даты: дата закрытия должна быть позже даты открытия';
$string['log_retention_days'] = 'Срок хранения логов (дней)';
$string['log_retention_days_desc'] = 'Через сколько дней автоматически удалять записи из журнала (0 - не удалять)';
$string['missing_idnumber'] = 'В курсе "{$a->coursename}" отсутствует тест с idnumber = "{$a->idnumber}"';
$string['new_dates'] = 'Новые даты';
$string['no_changes_made'] = 'Изменения не были применены';
$string['no_courses_found'] = 'В выбранной категории не найдено курсов';
$string['no_limit'] = 'без ограничений';
$string['nochanges'] = 'Без изменений';
$string['not_selected'] = 'не выбрано';
$string['notfound'] = 'Не найдено';
$string['old_dates'] = 'Старые даты';
$string['pluginname'] = 'Управление датами экзаменов';
$string['preview'] = 'Предпросмотр';
$string['preview_heading'] = 'Просмотр дат экзаменов: {$a}';
$string['preview_menu'] = 'Просмотр дат экзаменов';
$string['preview_readonly_notice'] = 'Вы можете просматривать изменения дат экзаменов для этой категории, но у вас нет прав на их применение. Обратитесь к менеджеру категории, если изменения необходимы.';
$string['preview_stats'] = 'Статистика предпросмотра';
$string['preview_stats_message'] = 'Будет изменено: {$a->tests} тестов в {$a->courses} курсах. Пропущено (тесты не найдены): {$a->errors}.';
$string['privacy:metadata:local_examdates_log'] = 'Журнал изменений дат экзаменов, выполненных пользователями.';
$string['privacy:metadata:local_examdates_log:courseid'] = 'ID курса, в котором изменён тест.';
$string['privacy:metadata:local_examdates_log:ip_address'] = 'IP-адрес, с которого было выполнено изменение.';
$string['privacy:metadata:local_examdates_log:quizid'] = 'ID теста, у которого изменены даты.';
$string['privacy:metadata:local_examdates_log:timecreated'] = 'Дата и время выполнения изменения.';
$string['privacy:metadata:local_examdates_log:userid'] = 'ID пользователя, выполнившего изменение.';
$string['quiz'] = 'Тест';
$string['records_total'] = 'Всего записей: {$a}';
$string['reset_filters'] = 'Сбросить фильтры';
$string['resit1'] = 'Пересдача 1';
$string['resit2'] = 'Пересдача 2';
$string['rollback'] = 'Откатить';
$string['rollback_confirm'] = 'Вы уверены, что хотите откатить изменения?';
$string['rollback_error'] = 'Ошибка отката для {$a->quizname} в курсе {$a->coursename}';
$string['rollback_notice'] = 'Откат возможен только для последнего изменения каждого теста';
$string['rollback_success'] = 'Успешный откат для {$a->quizname} в курсе {$a->coursename}';
$string['select_at_least_one'] = 'Выберите хотя бы один тип теста для обновления';
$string['settings'] = 'Настройки';
$string['show_filters'] = 'Показать фильтры';
$string['task_apply_updates'] = 'Применение массового изменения дат экзаменов';
$string['task_clean_logs'] = 'Очистка устаревших записей журнала дат экзаменов';
$string['update_exam_dates'] = 'Изменить сроки экзамена';
$string['update_resit1_dates'] = 'Изменить сроки пересдачи 1';
$string['update_resit2_dates'] = 'Изменить сроки пересдачи 2';
$string['view_history'] = 'Просмотр истории изменений';
