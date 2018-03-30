<?php
include("db.php");
use Asket\DB as DB;

include "NotifyUser.php";
use Asket\NotifyUser as NotifyUser;

include  "ExtendedDataOfUser.php";
use Asket\ExtendedDataOfUser as ExtendedDataOfUser;
class AgentFunction
{
    Const START_STATUS_LEAD = "NEW";
    Const USER_FIELD_LEAD_NOT_PROCCESSED_ONE_HOUR = "UF_CRM_1521733834";
    Const USER_FIELD_LEAD_NOT_PROCCESSED_TWO_HOUR = "UF_CRM_1521733987";

    function __construct($action)
    {
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
        $ErrorMessage = "";
        if (!(CModule::IncludeModule('main'))) {
            $ErrorMessage .= " Не загружен модуль 'main' \n";
        }
        if (!(CModule::IncludeModule('crm'))) {
            $ErrorMessage .= " Не загружен модуль 'crm' \n";
        }
        if (!(CModule::IncludeModule('intranet'))) {
            $ErrorMessage .= " Не загружен модуль 'intranet' \n";
        }
        if (!(CModule::IncludeModule('im'))) {
            $ErrorMessage .= " Не загружен модуль 'im' \n";
        }

        if ((strlen($ErrorMessage) == 0) && (strlen($ErrorMessage)==0)){
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
        }else{
            NotifyUser::SendEmailToUser(77, "Ошибка выполнения агента. ", "Во время выполнения агента об уведомлении о не взятом в работу лиде произошла ошибка. Описание ошибки ".$ErrorMessage);
        }

    }
}


