<?php

class crmProcessingNotification
{
    Const START_STATUS_LEAD = "NEW";
    Const USER_FIELD_LEAD_NOT_PROCCESSED_ONE_HOUR = "UF_CRM_1522661904";
    Const USER_FIELD_LEAD_NOT_PROCCESSED_TWO_HOUR = "UF_CRM_1522661930";
    Const DEFAULT_USER_FOR_NOTICE = 36;

    function __construct($Action)
    {
        switch ($Action) {
            case "sendUserNotifyAboutOldLead" :
                $this->sendUserNotifyAboutOldLead();
                break;
        }
    }

    function sendUserNotifyAboutOldLead()
    {
        $arFilter = array("TITLE" => Array("Заявка на лизинг", "Обратный звонок"), "STATUS_ID" => self::START_STATUS_LEAD, "CHECK_PERMISSIONS" => "N");
        $arOrder = Array('ID' => 'ASC');
        $arSelectFields = array("ID", "ASSIGNED_BY_ID", "DATE_CREATE", "STATUS_ID", self::USER_FIELD_LEAD_NOT_PROCCESSED_ONE_HOUR, self::USER_FIELD_LEAD_NOT_PROCCESSED_TWO_HOUR, "TITLE");
        $CrmLeads = CCrmLead::GetList($arOrder, $arFilter, $arSelectFields);
        $db = db::getInstance();
        while ($CrmLead = $CrmLeads->GetNext()) {
            $StrSQL = "SELECT stmain.dbo.sf_GetWorkTime ('" . date('m.d.y G:i:s', strtotime($CrmLead['DATE_CREATE'])) . "',GetDate()) as WorkTime";
            foreach ($db->Query($StrSQL) as $row) {
                $StrMessage = "";
                if (($row['WorkTime'] >= 1) && ($row['WorkTime'] <= 2)) {
                    if (!($CrmLead[self::USER_FIELD_LEAD_NOT_PROCCESSED_ONE_HOUR] == 1)) {
                        $StrMessage = "Прошел час. Лид  <a href = \" http://bitrix/crm/lead/show/" . $CrmLead["ID"] . "/ \" > " . $CrmLead["TITLE"] . " </a> не актуализирован";
                        $Field[self::USER_FIELD_LEAD_NOT_PROCCESSED_ONE_HOUR] = 1;
                    }
                } elseif ($row['WorkTime'] > 2) {
                    if (!($CrmLead[self::USER_FIELD_LEAD_NOT_PROCCESSED_TWO_HOUR] == 1)) {
                        $StrMessage = "Прошло два часа. Лид  <a href = \" http://bitrix/crm/lead/show/" . $CrmLead["ID"] . "/ \" >" . $CrmLead["TITLE"] . "</a> не актуализирован";
                        $Field[self::USER_FIELD_LEAD_NOT_PROCCESSED_TWO_HOUR] = 1;
                    }
                }
                if (strlen($StrMessage) > 0) {
                    if ($row['WorkTime'] > 2) {
                        $HeadOfUserId = extendedDataOfUser::GetHeadOfUserById($CrmLead["ASSIGNED_BY_ID"])[0];
                        notifyUser::sendNotifyToUser($HeadOfUserId, $StrMessage);
                        $arEventFields = array("MESSAGE" => $StrMessage, "EMAIL_TO" => extendedDataOfUser::getBitrixUserEmailById($HeadOfUserId));
                        CEvent::Send("Notify_About_Old_Lead", "s1", $arEventFields);
                    }
                    notifyUser::sendNotifyToUser(self::DEFAULT_USER_FOR_NOTICE, $StrMessage);
                    $arEventFields = array("MESSAGE" => $StrMessage, "EMAIL_TO" => extendedDataOfUser::getBitrixUserEmailById(self::DEFAULT_USER_FOR_NOTICE));
                    CEvent::Send("Notify_About_Old_Lead", "s1", $arEventFields);
                    $Lead = new CCrmLead(false);
                    $Opt = array('CURRENT_USER' => 27);
                    $Lead->Update($CrmLead["ID"], $Field, false, true, $Opt);
                }
            }
        }
    }
}


