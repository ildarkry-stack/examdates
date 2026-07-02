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

// Основное.
$string['pluginname'] = 'Управление датами экзаменов';
$string['examdates'] = 'Даты экзаменов';
$string['manageexamdates'] = 'Управление датами экзаменов';
$string['examdates:manage'] = 'Управление датами экзаменационных тестов';
$string['examdates:preview'] = 'Просмотр изменений дат экзаменов';
$string['examdates:bulkupdate'] = 'Массовое обновление дат экзаменов через CLI';

// Типы тестов.
$string['exam'] = 'Экзамен';
$string['resit1'] = 'Пересдача 1';
$string['resit2'] = 'Пересдача 2';
$string['quiztype'] = 'Тип теста';

// Форма выбора категории.
$string['category'] = 'Категория курсов';
$string['category_desc'] = 'Выберите категорию, в которой находятся курсы для обновления';
$string['include_subcategories'] = 'Включая подкатегории';
$string['include_subcategories_desc'] = 'Применять изменения также ко всем подкатегориям выбранной категории';

// Даты и время.
$string['date'] = 'Дата';
$string['dateopen'] = 'Дата и время открытия';
$string['dateclose'] = 'Дата и время закрытия';
$string['update_dates'] = 'Обновлять даты';
$string['select_at_least_one'] = 'Выберите хотя бы один тип теста для обновления';
$string['exam_open'] = 'Дата открытия экзамена';
$string['exam_close'] = 'Дата закрытия экзамена';
$string['resit1_open'] = 'Дата открытия пересдачи 1';
$string['resit1_close'] = 'Дата закрытия пересдачи 1';
$string['resit2_open'] = 'Дата открытия пересдачи 2';
$string['resit2_close'] = 'Дата закрытия пересдачи 2';

// Кнопки и действия.
$string['preview'] = 'Предпросмотр';
$string['apply'] = 'Применить изменения';
$string['apply_confirm'] = 'Вы уверены, что хотите применить изменения?';
$string['cancel'] = 'Отмена';
$string['back'] = 'Назад';
$string['continue'] = 'Продолжить';
$string['rollback'] = 'Откатить';
$string['rollback_confirm'] = 'Вы уверены, что хотите откатить изменения?';
$string['refresh'] = 'Обновить';
$string['update_exam_dates'] = 'Изменить сроки экзамена';
$string['update_resit1_dates'] = 'Изменить сроки пересдачи 1';
$string['update_resit2_dates'] = 'Изменить сроки пересдачи 2';
$string['not_selected'] = 'не выбрано';

// Результаты и статусы.
$string['found'] = 'Найдено';
$string['notfound'] = 'Не найдено';
$string['found_quizzes'] = 'Найдено тестов';
$string['errors'] = 'Пропущенные тесты / ошибки';
$string['success'] = 'Успешно';
$string['warning'] = 'Предупреждение';
$string['error'] = 'Ошибка';
$string['skipped'] = 'Пропущено';
$string['nochanges'] = 'Без изменений';
$string['updated'] = 'Обновлено';
$string['failed'] = 'Не удалось';
$string['status'] = 'Статус';

// Сообщения.
$string['no_courses_found'] = 'В выбранной категории не найдено курсов';
$string['no_quizzes_found'] = 'В курсе "{$a}" не найдены тесты с идентификаторами exam/resit1/resit2';
$string['no_changes_made'] = 'Изменения не были применены';
$string['changes_applied'] = 'Изменения успешно применены для {$a} курсов';
$string['changes_applied_detailed'] = 'Изменения успешно применены: {$a->tests} тестов в {$a->courses} курсах';
$string['changes_preview'] = 'Предпросмотр изменений';
$string['update_success'] = 'Даты теста "{$a->quizname}" в курсе "{$a->coursename}" успешно обновлены';
$string['update_error'] = 'Ошибка при обновлении теста "{$a->quizname}" в курсе "{$a->coursename}"';
$string['missing_idnumber'] = 'В курсе "{$a->coursename}" отсутствует тест с idnumber = "{$a->idnumber}"';
$string['invalid_dates'] = 'Некорректные даты: дата закрытия должна быть позже даты открытия';
$string['date_must_be_future'] = 'Дата должна быть в будущем';
$string['timezone_warning'] = 'Внимание: время отображается в часовом поясе сервера ({$a})';

// История изменений.
$string['history'] = 'История изменений';
$string['history_title'] = 'Журнал изменений дат экзаменов';
$string['history_empty'] = 'История изменений пуста';
$string['changed_by'] = 'Кто изменил';
$string['changed_at'] = 'Когда изменено';
$string['course'] = 'Курс';
$string['quiz'] = 'Тест';
$string['old_dates'] = 'Старые даты';
$string['new_dates'] = 'Новые даты';
$string['old_date_range'] = 'Старый период: {$a->open} — {$a->close}';
$string['new_date_range'] = 'Новый период: {$a->open} — {$a->close}';
$string['date_format'] = 'd.m.Y H:i';
$string['no_limit'] = 'без ограничений';
$string['batch_operation'] = 'Массовая операция';

// Фильтры в истории.
$string['filter_course'] = 'Фильтр по курсу';
$string['filter_user'] = 'Фильтр по пользователю';
$string['filter_date_from'] = 'Период с';
$string['filter_date_to'] = 'Период по';
$string['filter_idnumber'] = 'Тип теста';
$string['show_filters'] = 'Показать фильтры';
$string['reset_filters'] = 'Сбросить фильтры';
$string['export_csv'] = 'Экспорт в CSV';
$string['perpage'] = 'Записей на странице';
$string['records_total'] = 'Всего записей: {$a}';
 = 'Экспорт в CSV';
 = 'Записей на странице';
 = 'Всего записей: {}';

// Откат изменений.
$string['rollback_success'] = 'Успешный откат для {$a->quizname} в курсе {$a->coursename}';
$string['rollback_error'] = 'Ошибка отката для {$a->quizname} в курсе {$a->coursename}';
$string['rollback_notice'] = 'Откат возможен только для последнего изменения каждого теста';

// Отчёты.
$string['report'] = 'Отчёт';
$string['report_summary'] = 'Сводный отчёт';
$string['total_courses'] = 'Всего курсов';
$string['total_updates'] = 'Всего обновлений';
$string['last_update'] = 'Последнее обновление';
$string['most_active_user'] = 'Самый активный пользователь';
$string['most_updated_course'] = 'Курс с наибольшим количеством изменений';

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
$string['error_nopermission_preview'] = 'У вас нет прав для просмотра дат экзаменов';

// CLI скрипты.
$string['cli_usage'] = 'Использование: php update_exam_dates.php --categoryid=ID --examopen="YYYY-MM-DD HH:MM" --examclose="..." [--resit1open=...] [--resit2open=...] [--dryrun]';
$string['cli_dryrun'] = 'Режим предпросмотра (изменения не будут сохранены)';
$string['cli_success'] = 'Обновлено {$a->updated} тестов из {$a->total}';
$string['cli_error_category'] = 'Категория с ID {$a} не найдена';

// Help-подсказки.
$string['helpexam'] = 'Экзаменационный тест (участвует в формуле как [[exam]])';
$string['helpresit1'] = 'Первая пересдача (участвует в формуле как [[resit1]])';
$string['helpresit2'] = 'Вторая пересдача (участвует в формуле как [[resit2]])';
$string['helpidnumber'] = 'Тесты должны иметь idnumber: exam, resit1, resit2';
$string['helpcategory'] = 'Будут обработаны все курсы в выбранной категории';

// Предпросмотр и подтверждение.
$string['preview_stats'] = 'Статистика предпросмотра';
$string['confirm_apply_title'] = 'Подтверждение применения изменений';
$string['confirm_apply_text'] = 'Будет изменено <strong>{$a->tests}</strong> тестов в <strong>{$a->courses}</strong> курсах. Продолжить?';
$string['confirm_apply_button'] = 'Да, применить изменения';
$string['view_history'] = 'Просмотр истории изменений';

// ID number.
$string['idnumber'] = 'Идентификационный номер (ID number)';
$string['idnumber_required'] = 'ID number обязателен для заполнения';
$string['idnumber_desc'] = 'ID number теста в настройках модуля (например: exam, final_test, exam_2024)';
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