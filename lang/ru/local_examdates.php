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

// phpcs:disable moodle.Commenting.InlineComment.NotCapital
// (Every comment below already starts with a capital Cyrillic letter; this
// sniff uses ctype_upper(), which does not recognise multibyte Cyrillic.)

// Основное.
$string['pluginname'] = 'Управление датами экзаменов';
$string['examdates'] = 'Даты экзаменов';
$string['examdates:manage'] = 'Управление датами экзаменационных тестов';
$string['examdates:preview'] = 'Просмотр изменений дат экзаменов';
$string['examdates:bulkupdate'] = 'Массовое обновление дат экзаменов через CLI';

// Типы тестов.
$string['exam'] = 'Экзамен';
$string['resit1'] = 'Пересдача 1';
$string['resit2'] = 'Пересдача 2';

// Форма выбора категории.
$string['category'] = 'Категория курсов';
$string['include_subcategories'] = 'Включая подкатегории';

// Даты и время.
$string['dateopen'] = 'Дата и время открытия';
$string['dateclose'] = 'Дата и время закрытия';
$string['select_at_least_one'] = 'Выберите хотя бы один тип теста для обновления';

// Кнопки и действия.
$string['preview'] = 'Предпросмотр';
$string['apply'] = 'Применить изменения';
$string['cancel'] = 'Отмена';
$string['back'] = 'Назад';
$string['rollback'] = 'Откатить';
$string['rollback_confirm'] = 'Вы уверены, что хотите откатить изменения?';
$string['update_exam_dates'] = 'Изменить сроки экзамена';
$string['update_resit1_dates'] = 'Изменить сроки пересдачи 1';
$string['update_resit2_dates'] = 'Изменить сроки пересдачи 2';
$string['not_selected'] = 'не выбрано';

// Результаты и статусы.
$string['notfound'] = 'Не найдено';
$string['found_quizzes'] = 'Найдено тестов';
$string['errors'] = 'Пропущенные тесты / ошибки';
$string['error'] = 'Ошибка';
$string['nochanges'] = 'Без изменений';
$string['updated'] = 'Обновлено';
$string['status'] = 'Статус';

// Сообщения.
$string['no_courses_found'] = 'В выбранной категории не найдено курсов';
$string['no_changes_made'] = 'Изменения не были применены';
$string['changes_applied_detailed'] = 'Изменения успешно применены: {$a->tests} тестов в {$a->courses} курсах';
$string['missing_idnumber'] = 'В курсе "{$a->coursename}" отсутствует тест с idnumber = "{$a->idnumber}"';
$string['invalid_dates'] = 'Некорректные даты: дата закрытия должна быть позже даты открытия';

// История изменений.
$string['history_title'] = 'Журнал изменений дат экзаменов';
$string['history_empty'] = 'История изменений пуста';
$string['changed_by'] = 'Кто изменил';
$string['changed_at'] = 'Когда изменено';
$string['course'] = 'Курс';
$string['quiz'] = 'Тест';
$string['old_dates'] = 'Старые даты';
$string['new_dates'] = 'Новые даты';
$string['no_limit'] = 'без ограничений';

// Фильтры в истории.
$string['filter_course'] = 'Фильтр по курсу';
$string['filter_user'] = 'Фильтр по пользователю';
$string['filter_date_from'] = 'Период с';
$string['filter_date_to'] = 'Период по';
$string['filter_idnumber'] = 'Тип теста';
$string['show_filters'] = 'Показать фильтры';
$string['reset_filters'] = 'Сбросить фильтры';
$string['export_csv'] = 'Экспорт в CSV';
$string['records_total'] = 'Всего записей: {$a}';

// Откат изменений.
$string['rollback_success'] = 'Успешный откат для {$a->quizname} в курсе {$a->coursename}';
$string['rollback_error'] = 'Ошибка отката для {$a->quizname} в курсе {$a->coursename}';
$string['rollback_notice'] = 'Откат возможен только для последнего изменения каждого теста';

// Настройки плагина.
$string['settings'] = 'Настройки';
$string['default_category'] = 'Категория по умолчанию';
$string['default_category_desc'] = 'Категория, которая будет выбрана по умолчанию при открытии страницы управления';
$string['enable_logging'] = 'Включить логирование';
$string['enable_logging_desc'] = 'Записывать все изменения дат в журнал (рекомендуется). Отключение также отключает историю и откат.';
$string['log_retention_days'] = 'Срок хранения логов (дней)';
$string['log_retention_days_desc'] = 'Через сколько дней автоматически удалять записи из журнала (0 - не удалять)';

// Права доступа.
$string['error_nopermission'] = 'У вас нет прав для управления датами экзаменов в этой категории';
$string['error_lognotfound'] = 'Запись журнала не найдена';
$string['error_coursedeleted'] = 'Невозможно откатить: курс больше не существует';
$string['error_quizdeleted'] = 'Невозможно откатить: тест больше не существует';
$string['course_deleted'] = 'Курс удалён';
$string['preview_menu'] = 'Просмотр дат экзаменов';
$string['preview_heading'] = 'Просмотр дат экзаменов: {$a}';
$string['preview_readonly_notice'] = 'Вы можете просматривать изменения дат экзаменов для этой категории, но у вас нет прав на их применение. Обратитесь к менеджеру категории, если изменения необходимы.';
$string['go_to_manage'] = 'Перейти к управлению';
$string['error_nopermission_preview'] = 'У вас нет прав для просмотра дат экзаменов';

// CLI скрипты.
$string['cli_usage'] = 'Использование: php update_exam_dates.php --categoryid=ID --examopen="YYYY-MM-DD HH:MM" --examclose="..." [--resit1open=...] [--resit2open=...] [--dryrun]';
$string['cli_dryrun'] = 'Режим предпросмотра (изменения не будут сохранены)';
$string['cli_success'] = 'Обновлено {$a->updated} тестов из {$a->total}';
$string['cli_error_category'] = 'Категория с ID {$a} не найдена';

// Предпросмотр и подтверждение.
$string['preview_stats'] = 'Статистика предпросмотра';
$string['confirm_apply_title'] = 'Подтверждение применения изменений';
$string['confirm_apply_text'] = 'Будет изменено <strong>{$a->tests}</strong> тестов в <strong>{$a->courses}</strong> курсах. Продолжить?';
$string['view_history'] = 'Просмотр истории изменений';

// ID number.
$string['idnumber'] = 'Идентификационный номер (ID number)';
$string['idnumber_required'] = 'ID number обязателен для заполнения';
$string['idnumber_help'] = 'Идентификационный номер (ID number) теста, который вы задали в настройках модуля "Тест". Обычно используется "exam", "resit1", "resit2", но вы можете указать любое уникальное значение.';

// Прочее.
$string['arrow'] = '→';
$string['preview_stats_message'] = 'Будет изменено: {$a->tests} тестов в {$a->courses} курсах. Пропущено (тесты не найдены): {$a->errors}.';

// Действия (заголовок формы).
$string['actions'] = 'Действия';

// События.
$string['event_dates_updated'] = 'Даты экзаменов обновлены';

// Запланированные задачи.
$string['task_clean_logs'] = 'Очистка устаревших записей журнала дат экзаменов';

// Приватность (GDPR).
$string['privacy:metadata:local_examdates_log'] = 'Журнал изменений дат экзаменов, выполненных пользователями.';
$string['privacy:metadata:local_examdates_log:userid'] = 'ID пользователя, выполнившего изменение.';
$string['privacy:metadata:local_examdates_log:courseid'] = 'ID курса, в котором изменён тест.';
$string['privacy:metadata:local_examdates_log:quizid'] = 'ID теста, у которого изменены даты.';
$string['privacy:metadata:local_examdates_log:timecreated'] = 'Дата и время выполнения изменения.';
$string['privacy:metadata:local_examdates_log:ip_address'] = 'IP-адрес, с которого было выполнено изменение.';
