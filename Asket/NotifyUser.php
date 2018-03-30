<?php
namespace Asket;

class NotifyUser
{
    function SendNotifyToUser($UserID, $Message)
    {
        $arMessageFields = array(
            "TO_USER_ID" => $UserID,
            "FROM_USER_ID" => 1,
            "SYSTEM" => Y,
            "NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
            "NOTIFY_MODULE" => "CRM",
            "NOTIFY_TAG" => "",
            "NOTIFY_MESSAGE" => $Message,
        );
        \CIMNotify::Add($arMessageFields);
    }

    function SendEmailToUser($UserID, $Subject, $Body)
    {
        $UserArray = \CUser::GetById($UserID);
        $User = $UserArray->fetch();
        $header = "Content-type: text/html; charset=UTF-8 \r\n";
        mail($User["EMAIL"], $Subject, $Body, $header);
    }

}