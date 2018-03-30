<?php
namespace  Asket;
include "ExtendedDataOfUser.php";
use Asket\ExtendedDataOfUser as ExtendedDataOfUser;

class  LaunchBuisnessProcess
{

    //Код бизнес-процесса уведомления об увольнении пользователя
    const BP_NOTICE_DISMISS_ID = 107;
    //Код шаблона бизнес процесса уведомления об увольнении пользователя
    const BP_NOTICE_DISMISS_WORKFLOW_TEMPLATE_ID = 96;
    //Код пользователя Робот Аскет
    const ROBOT_ASKET_USER_ID = "user_27";

    function StartBusinessProcess($arFields = array(), $ID_BP)
    {
        if (!is_null($ID_BP)) {
            //$documentType = array("iblock", "CIBlockDocument"$ID_BP);;
            switch ($ID_BP) {
                case 107 :
                    return $this->StartBusinessProrcessNoticeDismiss($arFields);
                    break;
                default :
                    return "Туточки";
            }
        } else {
            return "Error. ID is null.";
        }
    }

    private function StartBusinessProrcessNoticeDismiss($arFields = array())
    {
        $ResultChekFields = $this->CheckFieldsForNoticeDismiss($arFields);
        if (!($ResultChekFields == 1)) {
            return ($ResultChekFields);
        }
        $ExtendedDataOfUser = new ExtendedDataOfUser();
        //Ищем пользователя, которого увольняем
        $FIO = $arFields["LName"] . " " . $arFields["FName"] . " " . $arFields["SName"];
        $DismissedUser = $ExtendedDataOfUser->GetBitrixUserIDByFIO($FIO);
        if ($DismissedUser == 0) {
            return "Error. Not found dismissed user with full name: " . $arFields['FName'] . " " . $arFields['SName'] . " " . $arFields['LName'];
        }

        //Ищем руководителя
        $FIO=  $arFields['LNameChief'] ." " . $arFields['FNameChief'] . " " . $arFields['SNameChief'] ;
        $ChiefUser =  $ExtendedDataOfUser->GetBitrixUserIDByFIO($FIO);
        if ($ChiefUser == 0) {
            return "Error. Not found chief with full name: " . $arFields['FName'] . " " . $arFields['SName'] . " " . $arFields['LName'];
        }
        $arParameters = array(
            "Dismissed_User" => "user_" . $DismissedUser,
            "Dismiss_date" => $arFields["LastDay"],
            "General" => "user_" . $ChiefUser,
            "Company" => $arFields["Company"]);
        //Если есть заместитель, то попробуейм найти его
        if (isset($arFields["SNameDeputy"]) || isset($arFields["FNameDeputy"]) || isset($arFields["LNameDeputy"])) {
            $FIO=  $arFields['LNameDeputy'] . " " . $arFields['FNameDeputy'] . " " . $arFields['SNameDeputy'];
            $DeputyUser = $ExtendedDataOfUser->GetBitrixUserIDByFIO($FIO);
            $arParameters["Deputy_param"] = "user_" . $DeputyUser;
        }
        $documentId = $this->CreateCBPVirtualDocument(self::BP_NOTICE_DISMISS_ID,
            "Увольнение сотрудника: " .  $arFields["FName"] . " " . $arFields["SName"]. $arFields["LName"] . " " ,
            self::ROBOT_ASKET_USER_ID
        );
        $documentId = array("lists", "BizprocDocument", $documentId);
        $arErrorsTmp = array();
        $WorkflowId = \CBPDocument::StartWorkflow(self::BP_NOTICE_DISMISS_WORKFLOW_TEMPLATE_ID, $documentId, $arParameters, $arErrorsTmp);
        if (empty($arErrorsTmp)) {
            return "Success. WorkflowId $WorkflowId";
        } else {
            return "Error. " . print_r($arErrorsTmp);
        }
    }

    private function CheckFieldsForNoticeDismiss($arFields = array())
    {
        $ArrErrors = array();
        if ((!isset($arFields["FName"])) || (empty($arFields["FName"]))) {
            $ArrErrors[] = "Parametr FName (First name of the dismissed user) is not set or is empty";
        }
        if ((!isset($arFields["SName"])) || (empty($arFields["SName"]))) {
            $ArrErrors[] = "Parametr SName (Second name of the dismissed user) is not set or is empty";
        }
        if ((!isset($arFields["LName"])) || (empty($arFields["LName"]))) {
            $ArrErrors[] = ".Parametr Lname (Last name of the dismissed user) is not set or is empty";
        }
        if ((!isset($arFields["FNameChief"])) || (empty($arFields["FNameChief"]))) {
            $ArrErrors[] = "Parametr FNameChief (First name chief) is not set or is empty";
        }
        if ((!isset($arFields["SNameChief"])) || (empty($arFields["SNameChief"]))) {
            $ArrErrors[] = "Parametr SNameChief (Second name chief) is not set or is empty";
        }
        if ((!isset($arFields["LNameChief"])) || (empty($arFields["LNameChief"]))) {
            $ArrErrors[] = "Parametr LNameChief (Last name chief) is not set or is empty";
        }
        if ((!isset($arFields["Company"])) || (empty($arFields["Company"]))) {
            $ArrErrors[] = "Parametr Company is not set or is empty";
        }
        if ((!isset($arFields["LastDay"])) || (empty($arFields["LastDay"]))) {
            $ArrErrors[] = "Parametr LastDay is not set or is empty";
        }
        if (Count($ArrErrors) > 0) {
            return "Error. " . implode(".", $ArrErrors);
        } else {
            return 1;
        }
    }

    private function CreateCBPVirtualDocument($IBLCOK_ID = "", $NAME = "", $CREATE_BY = "")
    {
        if (\CModule::IncludeModule('bizproc')) {
            return \CBPVirtualDocument::CreateDocument(
                0,
                array(
                    "IBLOCK_ID" => $IBLCOK_ID,
                    "NAME" => $NAME,
                    "CREATED_BY" => $CREATE_BY,
                )
            );
        }else{
            return "Error. Module bizproc is not loaded.";
        }

    }
}

