<?php
class editCRMLead
{

    const USER_FIELD_NAME_MODEL = "UF_CRM_1518178150";
    const USER_FIELD_TYPE_MODEL = "UF_CRM_1518178129";
    const USER_FIELD_INN = "UF_CRM_1480496082";
    const DEFAULT_MANAGER = 36;
    const PROPERTY_NAME_MODEL = "PROPERTY_337";
    const PROPERTY_TYPE_MODEL = "PROPERTY_338";
    const PROPERTY_MANAGER = "PROPERTY_333";
    const IBLOCK_ID_MODEL_FROM_MANAGER = 112;

    function __construct($arFields)
    {

        $this->setManagerOnLeadAdd($arFields);
    }

    function setManagerOnLeadAdd($arFields)
    {
        $db = db::getInstance();
        $ExtendedDataOfUser = new extendedDataOfUser();
        $Model = $arFields[self::USER_FIELD_NAME_MODEL];
        $Type = $arFields[self::USER_FIELD_TYPE_MODEL];
        $INN = preg_quote($arFields[self::USER_FIELD_INN]);
        $PhoneNumber = preg_replace('/[^0-9]/', '', $arFields['FM']['PHONE']['n1']['VALUE']);
        if (strlen($PhoneNumber) === 11) {
            $PhoneNumber = preg_replace("/^7|^8/", "", $PhoneNumber);
        }
        $email = $arFields['FM']['EMAIL']['n1']['VALUE'];
        $StrPattern = "((ООО)|(ЗАО)|(ПАО)|(ОАО)|(АО))|(\W)";
        $CompanyName = mb_eregi_replace($StrPattern, '', $arFields['COMPANY_TITLE']);
        $StrSQL = "select Top 1 Ф.ФИО 
                   from st.dbo.ДоговораЛ as д left join  st.dbo.Предприятия as п on д.КодЛизингополучателя =п.код
                                              left join st.dbo.Фамилии as Ф On Д.Отв = ф.КодГруппа
                    where  (Статус= 'Действует' or Статус= 'Закрыт') and  ";
        $UserId = 0;
        if (strlen($INN) > 0) {
            $StrSQL .= "П.ИНН = '$INN' and (Статус= 'Действует' or Статус= 'Закрыт') order by Д.НомерЧисло desc ";
            $UserId = $ExtendedDataOfUser->getBitrixUserIDByFullname($db->Query($StrSQL)[0][0]);
            if ($UserId != 0) {
                $arFields['COMMENTS'] .= " Ответственный назначен автоматически по ИНН";
            }
        }
        if (($UserId == 0) && (strlen($CompanyName) > 0)) {
            $StrSQL .= "(replace(replace(replace(replace(replace(replace(replace(П.Наименование,'ООО',''),'ОАО',''),'ЗАО',''),'ПАО',''),'АО',''),'\"',''),'''','') like '%$CompanyName%' or
                        replace(replace(replace(replace(replace(replace(replace(п.ПолноеНаименование,'ООО',''),'ОАО',''),'ЗАО',''),'ПАО',''),'АО',''),'\"',''),'''','') like '%$CompanyName%')
                        group by Ф.ФИО
                        order by max(д.НомерЧисло) desc";
            $UserId =$ExtendedDataOfUser->getBitrixUserIDByFullname($db->Query($StrSQL)[0][0]);
            if ($UserId != 0) {
                $arFields['COMMENTS'] .= " Ответственный назначен автоматически по названию компании";
            }
        }

        if (($UserId == 0) && (strlen($PhoneNumber) > 0)) {
            $StrSQL .= " КодЛизингополучателя in(  select КодПредпр from stmain.dbo.Телефоны where replace(replace(replace(replace(replace(Телефоны.Сотовый,'-',''),')',''),'(',''),'+',''),' ','') like '%$PhoneNumber%' or
                                                                                                           replace(replace(replace(replace(replace(Телефоны.Рабочий,'-',''),')',''),'(',''),'+',''),' ','') like '%$PhoneNumber%' or
                                                                                                           replace(replace(replace(replace(replace(Телефоны.Домашний ,'-',''),')',''),'(',''),'+',''),' ','') like '%$PhoneNumber%' or
                                                                                                           replace(replace(replace(replace(replace(Телефоны.Факс ,'-',''),')',''),'(',''),'+',''),' ','') like '%$PhoneNumber%') 
                                                   order by Д.НомерЧисло desc";
            $UserId =$ExtendedDataOfUser->getBitrixUserIDByFullname($db->Query($StrSQL)[0][0]);
            if ($UserId != 0) {
                $arFields['COMMENTS'] .= " Ответственный назначен автоматически по номеру телефона";
            }
        }
        if (($UserId == 0) && (strlen($email) > 0)) {
            $StrSQL .= "  КодЛизингополучателя in(  select КодПредпр from stmain.dbo.Телефоны where  Телефоны.ЭлПочта like '%$email%') order by Д.НомерЧисло desc";
            $UserId = $ExtendedDataOfUser->getBitrixUserIDByFullname($db->Query($StrSQL)[0][0]);
            if ($UserId != 0) {
                $arFields['COMMENTS'] .= " Ответственный назначен автоматически по адресу электронной почты";
            }
        }
        if (($UserId == 0) && (strlen($Model) > 0 && strlen($Type) > 0)) {
            $ArrSelect = Array("ID", "IBLOCK_ID", self::PROPERTY_MANAGER);
            $ArrFilter = Array("IBLOCK_ID" => self::IBLOCK_ID_MODEL_FROM_MANAGER, self::PROPERTY_NAME_MODEL => $Model, self::PROPERTY_TYPE_MODEL => $Type);

            $Lst = CIBlockElement::GetList(array(), $ArrFilter, false, Array("nPageSize" => 1), $ArrSelect);
            while ($ob = $Lst->GetNextElement()) {
                $UserId = $ob->GetFields()[self::PROPERTY_MANAGER . "_VALUE"];
            }
            if ($UserId != 0) {
                $arFields['COMMENTS'] .= " Ответственный назначен автоматически по марке. ";
            }
        }
        if ($UserId == 0) {
            $UserId = self::DEFAULT_MANAGER;
            $arFields['COMMENTS'] .= " Произошла ошибка при распознавании лида.";
        }
        $arFields['ASSIGNED_BY_ID'] = $UserId;
        $Lead = new CCrmLead;
        $Lead->Update($arFields["ID"], $arFields, true, true, array());
    }
}