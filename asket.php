<?

//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");

include $_SERVER["DOCUMENT_ROOT"]."/services/Asket/LaunchBuisnessProcess.php";
use Asket\LaunchBuisnessProcess as LaunchBuisnessProcess;

if (isset($_GET['pp']['filter'])){
    $arFilter = $_GET['pp']['filter'];
}else {
    if (isset($_GET['pp']['ID'])){
        $arFilter=$_GET['pp']['ID'];
    }else{
        $arFilter =array();
    }
}
if (isset($_GET['pp']['order'])){
    $arOrder=$_GET['pp']['order'];
}else{
    $arOrder=array();
}
if (isset($_GET['pp']['fields'])){
    $arSelectFields=$_GET['pp']['fields'];
}else{
    $arSelectFields=array('ID', 'TITLE', 'NAME', 'COMMENTS',  'ORIGINATOR_ID', 'ORIGIN_ID');
}

foreach ($_GET['pp'] as $item=>$value){
    $arFields[$item]=$value;
}

list($module,$obj,$action)=explode('.',$_GET['p']);
echo "<pre> ";
if (isset($_GET['p'])){
  CrmAction($module, $obj,$action,$arFilter,$arOrder,$arSelectFields,$arFields);
}

echo "</pre>";
function CrmAction($module="",$obj="",$action="",$arFilter,$arOrder,$arSelectFields,$arFields=array()){
    echo " {\"result\":";
    switch ($module){
            case "workflow":
            $bp =  new LaunchBuisnessProcess();
            echo "\"". $bp->StartBusinessProcess($arFields,$arFilter)." \"}";
            break;
        case "crm":
            switch ($action){
                case "list":
                    echo ",\"total\":".GetCrmList($obj,$arFilter,$arOrder,$arSelectFields)."}";  break;
                case "update":
                    echo UpdateCrm($obj,$arFilter,$arSelectFields)."}"; break;
                case "hello":
                    echo "Hello, Asket-Robot}";
                    break;
                case "report":
                    echo "this is place for beautiful report}";
                    break;
            };
            break;
    };
}
function GetCrmList($obj,$arFilter, $arOrder, $arSelectFields ){
    switch ($obj){
        case "deal":
            $dbElements = CCrmDeal::GetListEx($arOrder, $arFilter, $arGroupBy = false, array('nTopCount'=>100), $arSelectFields);
            break;
        case "lead":
            $dbElements = CCrmLead::GetListEx($arOrder, $arFilter, $arGroupBy = false, array('nTopCount'=>100), $arSelectFields);
            break;
        case "company":
            if (isset($arFilter['TITLE'])){
                $arFilter['TITLE'] = iconv("cp1251","utf-8",$arFilter['TITLE']);

            }
            $dbElements = CCrmCompany::GetListEx($arOrder, $arFilter, $arGroupBy = false, array('nTopCount'=>100), $arSelectFields);
            break;
        case "companybyrequisite":
            $requisite = new \Bitrix\Crm\EntityRequisite();
            $idCompany = $requisite->getList(["filter" => ["RQ_INN" => $arFilter['COMPANY_INN'],"ENTITY_TYPE_ID" => CCrmOwnerType::Company,]])->fetch()['ENTITY_ID'];
            $dbElements = CCrmCompany::GetListEx($arOrder, array("ID" => $idCompany,"ASSIGNED_BY_ID"=>$arFilter['ASSIGNED_BY_ID']), $arGroupBy = false, array('nTopCount'=>100), $arSelectFields);
            break;
        case "contact":
            $dbElements = CCrmContact::GetListEx($arOrder, $arFilter, $arGroupBy = false, array('nTopCount'=>100));
            break;
        case "phone":
            $dbElements = CCrmFieldMulti::GetList($arOrder, array('ENTITY_ID'=>'CONTACT','TYPE_ID'=>'PHONE','ELEMENT_ID'=>$arFilter));
            break;
        case "email":
            $dbElements = CCrmFieldMulti::GetList($arOrder, array('ENTITY_ID'=>'CONTACT','TYPE_ID'=>'EMAIL','ELEMENT_ID'=>$arFilter));
            break;
        case "requisite":
            $requisite = new \Bitrix\Crm\EntityRequisite();
            $dbElements = $requisite->getList(["filter" => ["ENTITY_ID" => $arFilter['COMPANY_ID'],"ENTITY_TYPE_ID" => CCrmOwnerType::Company,]]);
            $idAddress= $requisite->getList(["filter" => ["ENTITY_ID" => $arFilter['COMPANY_ID'],"ENTITY_TYPE_ID" => CCrmOwnerType::Company,]])->fetch()['ID'];
            $address = Bitrix\Crm\EntityRequisite::getAddresses($idAddress);
            break;
        case "product":
            $dbElements=  CCrmProductRow::GetList($arOrder, array('OWNER_ID'=>$arFilter), $arGroupBy = false, array('nTopCount'=>100), $arSelectFields = array(), $arOptions = array());
            break;
    }

    $dbElement = array();
    echo "[";
    while ($dbElements && ($dbElement = $dbElements->fetch()))
    {
        echo  CUtil::PhpToJsObject($dbElement);
    }
    if ($obj == "requisite"){
        foreach ($address as $key=> $addres)
        {
            echo  CUtil::PhpToJsObject((array($key=>$addres)));

        }
    }
    echo "]";
    return count($dbElements);
}
function UpdateCrm($obj,$id,$arFields){
    switch ($obj){
        case "deal": $objUpdate = new CCrmDeal;	break;
        case "lead": $objUpdate = new CCrmLead;	break;
        case "company":	$objUpdate = new CCrmCompany; break;
        case "product": $objUpdate = new  CCrmProductRow; break;
    }
    if (!(($id =="0") || ($id==""))){
        if($objUpdate->Update($id,$arFields, true, true, array('DISABLE_USER_FIELD_CHECK' => true))){
            return "\"Succes update " . $obj. "=". $id ."\"";
        }else{
            return "\"Error update " . $obj . $objUpdate->LAST_ERROR ."\"";
        }
    }
}
