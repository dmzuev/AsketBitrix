<?php

// Все переменные только camelCase.
// errorMessage, а не ErrorMessage.

// Используя namespace, ты показываешь коду часть файлов, которая существует "вместе",
// поэтому для классов из этого же namespace'а не надо будет указывать use.
namespace Asket;

// Алиасы для неймспейсов нужны тогда, когда у тебя есть два класса с одинаковыми названиями.
// Например,
// use Asket\DB as AsketDb;
// use 2kai\DB as 2kaiDb;

// Всегда используй полный путь до файла.
// Этим ты избежишь ошибок подключения (чтения, удаления) файлов вне контекста, когда текущая директория неизвестна.
// Поэтому я добавил __DIR__.

// А если ты будешь использовать autoloading, то тебе не придётся использовать include.
// http://php.net/manual/ru/language.oop5.autoload.php
include __DIR__ . '/db.php';
include __DIR__ . '/NotifyUser.php';
include __DIR__ . '/ExtendedDataOfUser.php';

// Название класса не очень.
// По названию ты должен хоть что-то понимать о назначении класса.
// Судя по коду это какая-то нотификация.
// Пусть тогда называется хотя бы CrmLeadProcessingNotification
class AgentFunction
{
    // const пишется строчными
    const START_STATUS_LEAD = "NEW";
    const USER_FIELD_LEAD_NOT_PROCCESSED_ONE_HOUR = "UF_CRM_1521733834";
    const USER_FIELD_LEAD_NOT_PROCCESSED_TWO_HOUR = "UF_CRM_1521733987";

    private $errorMessage;

    function __construct($action)
    {
        if ($this->areModulesIncluded()) {
            $arFilter = array("TITLE" => Array("Заявка на лизинг", "Обратный звонок"), "STATUS_ID" => self::START_STATUS_LEAD, "CHECK_PERMISSIONS" => "N");
            $arOrder = Array('ID' => 'ASC');
            $arSelectFields = array("ID", "ASSIGNED_BY_ID", "DATE_CREATE", "STATUS_ID", self::USER_FIELD_LEAD_NOT_PROCCESSED_ONE_HOUR, self::USER_FIELD_LEAD_NOT_PROCCESSED_TWO_HOUR, "TITLE");
            $CrmLeads = \CCrmLead::GetList($arOrder, $arFilter, $arSelectFields);
            $db = DB::getInstance();
            while ($CrmLead = $CrmLeads->GetNext()) {
                $StrSQL = "SELECT stmain.dbo.sf_GetWorkTime ('" . date('m.d.y G:i:s', strtotime($CrmLead['DATE_CREATE'])) . "',GetDate()) as WorkTime";
                foreach ($db->Query($StrSQL) as $row) {
                    $StrMessage = "";
                    if (($row['WorkTime'] >= 1) && ($row['WorkTime'] <= 2)) {
                        if (!($CrmLead[self::USER_FIELD_LEAD_NOT_PROCCESSED_ONE_HOUR] == 1)) {
                            $StrMessage = "Прошел час. Лид  <a href = \" http://qqq/crm/lead/show/" . $CrmLead["ID"] . "/ \" > " . $CrmLead["TITLE"] . " </a> не актуализирован";
                            $Field[self::USER_FIELD_LEAD_NOT_PROCCESSED_ONE_HOUR] = 1;
                        }
                    } elseif ($row['WorkTime'] > 2) {
                        if (!($CrmLead[self::USER_FIELD_LEAD_NOT_PROCCESSED_TWO_HOUR] == 1)) {
                            $StrMessage = "Прошло два часа. Лид  <a href = \" http://qqq/crm/lead/show/" . $CrmLead["ID"] . "/ \" >" . $CrmLead["TITLE"] . "</a> не актуализирован";
                            $Field[self::USER_FIELD_LEAD_NOT_PROCCESSED_TWO_HOUR] = 1;
                        }
                    }
                    if (strlen($StrMessage) > 0) {
                        if ($row['WorkTime'] > 2){
                            $HeadOfUserId =ExtendedDataOfUser::GetHeadOfUserById($CrmLead["ASSIGNED_BY_ID"])[0];
                            NotifyUser::SendEmailToUser($HeadOfUserId,"Новый лид не взяли в работу", $StrMessage );
                            NotifyUser::SendNotifyToUser($HeadOfUserId,$StrMessage  );
                        }
                        NotifyUser::SendEmailToUser(36, "Новый лид не взяли в работу", $StrMessage);
                        NotifyUser::SendNotifyToUser(36, $StrMessage);
                        $Lead = new CCrmLead;
                        $Lead->Update($CrmLead["ID"], $Field, true, true, array());
                    }
                }
            }
        } else {
            NotifyUser::SendEmailToUser(77, "Ошибка выполнения агента. ", "Во время выполнения агента об уведомлении о не взятом в работу лиде произошла ошибка. Описание ошибки ". $this->errorMessage);
        }
    }

    /**
     * Этот метод временный. Он нужен только для того, чтобы привести в порядок конструктор.
     * Весь его код нужно выносить из класса наверх.
     *
     * @return bool
     */
    private function areModulesIncluded()
    {
        // Никогда такого здесь не должно быть.
        // Это настройки аж php.ini, а не конструктора класса.
        // Если на это завязан код, то надо как-то аккуратнее сделать, иначе — удалить.
        error_reporting(E_ALL);
        ini_set("display_errors", 1);

        // Вряд ли в prolog_before.php есть что-то, что относится к этому классу,
        // так что надо вынести это наверх.
        require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

        // Вся эта проверка должна быть вынесена наверх.
        // Конструктор не должен заниматься проверкой каких-то неизвестных ему модулей.
        // Иначе нарушается один из основополагающих принципов - single responsibility.
        // Принцип единой ответственности — класс должен отвечать только за свою часть работы.
        $this->errorMessage = '';
        if (!(CModule::IncludeModule('main'))) {
            $this->errorMessage .= " Не загружен модуль 'main' \n";
        }
        if (!(CModule::IncludeModule('crm'))) {
            $this->errorMessage .= " Не загружен модуль 'crm' \n";
        }
        if (!(CModule::IncludeModule('intranet'))) {
            $this->errorMessage .= " Не загружен модуль 'intranet' \n";
        }
        if (!(CModule::IncludeModule('im'))) {
            $this->errorMessage .= " Не загружен модуль 'im' \n";
        }

        return empty($this->errorMessage);
    }
}


