<?

global $isMeetingBooked;
global $MeetingId;
$isMeetingBooked = 1;
$MeetingId=0;

CModule::AddAutoloadClasses('',array('bookingMeetingRooms' => '/services/Asket/bookingMeetingRooms.php'));
CModule::AddAutoloadClasses('',array('editCRMLead' => '/services/Asket/editCRMLead.php'));
CModule::AddAutoloadClasses('',array('db' => '/services/Asket/db.php'));
CModule::AddAutoloadClasses('',array('extendedDataOfUser' => '/services/Asket/extendedDataOfUser.php'));
CModule::AddAutoloadClasses('',array('extendedStr' => '/services/Asket/extendedStr.php'));
CModule::AddAutoloadClasses('',array('notifyUser' => '/services/Asket/notifyUser.php'));
CModule::AddAutoloadClasses('',array('crmProcessingNotification' => '/services/Asket/crmProcessingNotification.php'));

AddEventHandler("meeting", "OnBeforeMeetingUpdate", "BeforeMeetingUpdate");
AddEventHandler("iblock", "OnAfterIBlockElementAdd", "IBlockElementAdd");
AddEventHandler("iblock", "OnAfterIBlockElementDelete", "IBlockElementDelete"); 
AddEventHandler("iblock", "OnBeforeIBlockElementAdd", "IBlockElementBeforAdd");
AddEventHandler("crm", "OnAfterCrmLeadAdd", "CrmLeadAdd");


function IBlockElementDelete($arFields){
    If (($arFields['IBLOCK_ID'] == 67) ||  (!($MeetingId==0))){
        $mr = new bookingMeetingRooms("Delete","iblock",$arFields['ID'], $arFields);
    }
}
function IBlockElementAdd($arFields){
    If (($arFields['IBLOCK_ID'] == 67) ||  (!($MeetingId==0))){
        $mr = new bookingMeetingRooms("Add","iblock",$arFields['RESULT'], $arFields);
    }
}
function IBlockElementBeforAdd($arFields){
    global $MeetingId;
    global $isMeetingBooked;
    if (!($MeetingID==0)){
        $arSelect =""; //array('EVENT_ID'); //ECMR_227_658
        $arFilter= array('ID'=>$MeetingID);
        $res = CMeeting::GetList(Array("ID"=>"ASC"), $arFilter, false, Array("nPageSize"=>100), $arSelect);
        $EVENT_ID= $res->Fetch()['EVENT_ID'];
        $arFilter= array('ID'=>$arFields['ID']);
        $IBlockElID =CCalendarEvent::GetById($EVENT_ID, false)['LOCATION'];
        $IBlockElID =explode("_",$IBlockElID)[2];
        if ($IBlockElID != 0){
            $isMeetingBooked = 0;
            CIBlockElement::Delete($IBlockElID);
        }
    }
}
function BeforeMeetingUpdate($arFields){
    global 	$MeetingID;
    $MeetingID= $arFields['ID'];
}
function CrmLeadAdd($arFields){
    if (($arFields['CREATED_BY_ID'] ==27)|| ($arFields['CREATED_BY_ID']==77)){
        $crm = new editCRMLead($arFields);
    }

}
function sendUserNotifyAboutOldLead(){
    require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
    $ErrorMessage = "";
    $CurrentTime = date('Hi');
    if (($CurrentTime > 900) && ($CurrentTime < 1800)) {
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

        if ((strlen($ErrorMessage) == 0) && (strlen($ErrorMessage) == 0)) {
            $agent = new crmProcessingNotification ("sendUserNotifyAboutOldLead");
            $ErrorMessage = "";
        } else {
            $ErrorMessage .= "Во время выполнения агента Notify_About_Old_Lead произошла ошибка. Описание ошибки " . $ErrorMessage;

        }
    } else {
        $ErrorMessage = "Запуск агента sendUserNotifyAboutOldLead в не рабочее время ";
    }
    $arEventFields = array("AGENT_NAME" => htmlspecialcharsEx("Notify_About_Old_Lead"), "ERROR_MESSAGE" => $ErrorMessage, "EMAIL_TO" => "zuev@stone-xxi.ru");
    CEvent::Send("Notify_About_Successful_Notify", "s1", $arEventFields);
    return "sendUserNotifyAboutOldLead();";
}
?>
