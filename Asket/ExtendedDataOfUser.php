<?php
namespace Asket;

class ExtendedDataOfUser{
    Function GetHeadOfUserById($UserID)
    {
        $managers = array();
        $sections = \CIntranetUtils::GetUserDepartments($UserID);
        foreach ($sections as $section) {
            $manager = \CIntranetUtils::GetDepartmentManagerID($section);
            while (empty($manager)) {
                $res = \CIBlockSection::GetByID($section);
                if ($sectionInfo = $res->GetNext()) {
                    $manager = \CIntranetUtils::GetDepartmentManagerID($section);
                    $section = $sectionInfo['IBLOCK_SECTION_ID'];
                    if ($section < 1) break;
                } else break;
            }
            If ($manager > 0) $managers[] = $manager;
        }
        return $managers;
    }
    function GetBitrixUserIDByFIO($FIOManager)
    {
        if (strlen($FIOManager) > 0) {
            $User = \CUser::GetList($by = "id", $order = "desc", array("LAST_NAME" => mb_split(' ', $FIOManager)[0], "SECOND_NAME" => mb_split(' ', $FIOManager)[2], "NAME" => mb_split(' ', $FIOManager)[1]), array());
            return $User->fetch()["ID"];
        } else {
            return 0;
        }
    }


}