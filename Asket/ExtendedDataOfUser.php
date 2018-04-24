<?php

class extendedDataOfUser{
    Function getHeadOfUserById($UserID)
    {
        $managers = array();
        $sections = CIntranetUtils::GetUserDepartments($UserID);
        foreach ($sections as $section) {
            $manager = CIntranetUtils::GetDepartmentManagerID($section);
            while (empty($manager)) {
                $res = CIBlockSection::GetByID($section);
                if ($sectionInfo = $res->GetNext()) {
                    $manager = CIntranetUtils::GetDepartmentManagerID($section);
                    $section = $sectionInfo['IBLOCK_SECTION_ID'];
                    if ($section < 1) break;
                } else break;
            }
            If ($manager > 0) $managers[] = $manager;
        }
        return $managers;
    }
    Function getBitrixUserIDByFullname($Fullname)
    {
        if (strlen($Fullname) > 0) {
            $User = CUser::GetList($by = "id", $order = "desc", array("LAST_NAME" => mb_split(' ', $Fullname)[0], "SECOND_NAME" => mb_split(' ', $Fullname)[2], "NAME" => mb_split(' ', $Fullname)[1]), array());
            return $User->fetch()["ID"];
        } else {
            return 0;
        }
    }
    Function getBitrixUserEmailById($id)
    {
        $rsUser = CUser::GetByID($id);
        $arUser = $rsUser->Fetch();
        return $arUser['EMAIL'];
    }

}