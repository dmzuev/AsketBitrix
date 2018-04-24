<?php

class notifyUser
{
    function sendNotifyToUser($UserId, $Message)
    {
        $arMessageFields = array(
            "TO_USER_ID" => $UserId,
            "FROM_USER_ID" => 1,
            "SYSTEM" => Y,
            "NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
            "NOTIFY_MODULE" => "CRM",
            "NOTIFY_TAG" => "",
            "NOTIFY_MESSAGE" => $Message,
        );
        CIMNotify::Add($arMessageFields);
    }

    function sendEmailToUser($UserId, $Subject, $Body)
    {
        $UserArray = CUser::GetById($UserId);
        $User = $UserArray->fetch();
        $header = "From:admin_bitrix@stounxxi.ru; Content-type: text/html; charset=UTF-8 \r\n";
        mail($User["EMAIL"], $Subject, $Body, $header);
    }

}