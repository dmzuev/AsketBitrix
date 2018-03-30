<?php

include "NotifyUser.php";
use Asket\NotifyUser as NotifyUser;

include "db.php";
use Asket\DB as DB;


class BookingMeetingRooms
{
    private $StrL = "wwwOrders";
    private $password= "wwwOrders";
    private $Srv = "192.168.1.3";
    private $BitrixRooms = array(188 => 1,    #600
        189 => 2,    #607
        187 => 3,    #405
        224 => 4,    #36
        225 => 5,    #206a
        227 => 6,    #409
        226 => 7 #7
    );
    private $qqqRooms = array(40 => 1,    #600
        41 => 2,    #607
        39 => 3,    #405
        57 => 4,    #36
        58 => 5,    #206a
        59 => 6    #409
        #226=>7 #7
    );


    function __construct($Act, $Source, $ID, $arFields)
    {
        error_reporting(E_ERROR);
        ini_set("display_errors", 1);
        if ($arFields['IBLOCK_ID'] == 67) {
            switch ($Act) {
                case "Add":
                    $this->CreateMeetingRooms($ID, $Source);
                    break;
                case "Delete" :
                    $this->DeleteMeetingRooms($ID, $Source);
                    break;
                case "Edit":
                    $this->DeleteMeetingRooms($ID, $Source);
                    $this->CreateMeetingRooms($ID, $Source);
                    break;
            }
        }
    }

    Function DeleteMeetingRooms($ID, $Source)
    {
        global $USER;
        global $bool_Meeting;
        if ($Source == "iblock") {
            $StrSQL = "Select RoomReserving_ID from inmail.dbo.RoomReserving_BitrixReserveMiting where Item_ID=$ID";
        } elseif (($Source == "meeting") || ($Source == "calendar")) {
            $StrSQL = "Select RoomReserving_ID from inmail.dbo.RoomReserving_BitrixEvent where Event_ID=$ID";
        }

        $db = DB::getInstance();
        foreach ($db->Query($StrSQL) as $row) {
            if (strlen($StrRRID) == 0) {
                $StrRRID = $row[0];
            } else {
                $StrRRID .= ",$row[0]";
            }
            $db->Query("delete from  inmail.dbo.RoomReserving where id=$row[0]");
            
            if ($Source == "iblock") {
                mssql_query("delete from inmail.dbo.RoomReserving_BitrixReserveMiting where Item_ID=$ID");
            } elseif (($Source == "meeting") || ($Source == "calendar")) {
                mssql_query("delete from inmail.dbo.RoomReserving_BitrixEvent where EVENT_ID=$ID");
            }
            $Msg = "Переговорная на сайте www освобождена";
            if ($bool_Meeting == 1) {
                NotifyUser::SendNotifyToUser($USER->GetId(), $Msg);
            }
        }
    }

    Function CreateMeetingRooms($ID, $Source)
    {
        global $USER;
        $EventId = 0;
        if ($Source == "iblock") {
            $arSelect = "";
            $arFilter = Array("IBLOCK_ID" => 67, "ID" => $ID);
            $res = \CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, Array("nPageSize" => 100), $arSelect);
            $ob = $res->GetNextElement();
            $arFields = $ob->GetFields();
            $PlaceID = $arFields['IBLOCK_SECTION_ID'];
            $DateFrom = date_create($arFields['ACTIVE_FROM']);
            $DateTo = date_create($arFields["ACTIVE_TO"]);
        } else {
            if ($Source == "meeting") {
                $arFilter = array('ID' => $ID);
            } elseif ($Source == "calendar") {
                $arFilter = array('EVENT_ID' => $ID);
            }
            $arSelect = array("PLACE");
            $dbElements = \CMeeting::GetList(Array("SORT" => "ASC"), $arFilter, false, false, $arSelect);
            $dbElements = $dbElements->Fetch();
            $PlaceID = explode("_", $dbElements['PLACE'])[2];
            $dbElements = CCalendarEvent::GetById($ID);
            $DateFrom = date_create($dbElements['DATE_FROM']);
            $DateTo = date_create($dbElements["DATE_TO"]);
        }

        $DateFromMin = $DateFrom->format("i");
        $DateToMin = $DateTo->format("i");
        if (($DateToMin > 0) && ($DateToMin <= 30)) {
            $DateToMin = "30";
        } elseif (($DateToMin > 30)) {
            $DateTo->add(new DateInterval("PT1H"));
            $DateToMin = "00";
        }
        if (($DateFromMin > 0) && ($DateFromMin < 30)) {
            $DateFromMin = "00";
        } elseif ($DateFromMin >= 30) {
            $DateFromMin = "30";
        }

        //Узнаем на какое время бронируем
        $StrSQL = "select id from inmail.dbo.ReservingTime where Time >= '02.03.2010 " . date_format($DateFrom, "H") . ":" . $DateFromMin . ":00' and Time < '02.03.2010 " . date_format($DateTo, "H") . ":" . $DateToMin . ":00'";
        $db = DB::getInstance();
        $StrIdTime = "";
        foreach ($db->Query($StrSQL) as $row){
            if (strlen($StrIdTime) == 0) {
                $StrIdTime = $row[0];
            } else {
                $StrIdTime .= "," .$row[0];
            }
        }

        //Узнаем кто хочет забронировать
        $StrSQL = "Select top 1 id from inmail.dbo.Stone_Users where email = '" . $USER->GetEmail() . "'";
        $IDUser =$db->Query($StrSQL)[0][0];

        If ((strlen($StrIdTime) > 0) && !($IDUser == null) && (strlen($PlaceID) > 0)) {
            //Если переговорная свободная в это время
            $StrSQL = "Select id from inmail.dbo.RoomReserving where Room_ID=" . $this->BitrixRooms[$PlaceID] . " and time_Id in ($StrIdTime) and (Date >= '" . date_format($DateFrom, "m.d.y H") . ":" . $DateFromMin . ":00' and Date <  '" . date_format($DateTo, "m.d.y H") . ":" . $DateToMin . ":00')";

            if (Count($db->Query($StrSQL)) == 0) {
                $StrSQL = "select id,Time from inmail.dbo.ReservingTime where Time >= '02.03.2010 " . date_format($DateFrom, "H") . ":" . $DateFromMin . ":00' and Time < '02.03.2010 " . date_format($DateTo, "H") . ":" . $DateToMin . ":00'";
                $Q_RTime = $db->Query($StrSQL);
                foreach ($Q_RTime as $row) {
                    $StrSQL = "Insert into inmail.dbo.RoomReserving(Date,Room_ID,USER_ID,Time_ID) Values('" . date_format($DateFrom, "m.d.y ") . date_format(date_create($row[1]), "H:i:s") . "', " . $this->BitrixRooms[$PlaceID] . ",$IDUser, " . $row[0] . ")";
                    $db->Query($StrSQL);
                    $StrSQL  = "select ident_current('inmail.dbo.RoomReserving')";
                    foreach ($db->Query($StrSQL) as $row )
                    {
                        if ($Source = 'iblock') {
                            $db->Query("Insert into inmail.dbo.RoomReserving_BitrixReserveMiting(Item_ID,RoomReserving_ID) Values($ID," . $row[0] . ")") ;
                        } elseif (($Source == "meetin") || ($Source == "calendar")) {
                            $db->Query("Insert into inmail.dbo.RoomReserving_BitrixEvent(EVENT_ID,RoomReserving_ID) Values($ID," . $row[0] . ")");
                        }
                    }
                    $Msg = "Переговорная на сайте www забронирована";
                }
            } else {
                $Msg = "Возникла ошибка при бронирование переговорной на сайте www. Возможно, перегорная уже кем-то занята на это время ";
            }
        } else {
            $Msg = "Возникла ошибка при бронирование переговорной на сайте www. Возможно, перегорная уже кем-то занята на это время ";
        }
        NotifyUser::SendNotifyToUser($USER->GetId(), $Msg);
    }

}
