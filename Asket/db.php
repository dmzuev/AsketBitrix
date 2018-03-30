<?php
namespace  Asket;

include "ExtendedStr.php";
use Asket\ExtendedStr  as ExtendedStr;

use PDO as PDO;
class DB{
    private $dbst = "st";
    private $dbmain = "stmain";
    private $user = "wwwOrders";
    private $password= "wwwOrders";
    private $host = "192.168.1.3";

    private  $CnMain;
    private static $instance = null;
    private function __clone() {}
    private function __wakeup() {}

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        try {
            $this->CnMain = new PDO ("dblib:host=$this->host;dbname=$this->dbmain;charset=cp1251", "$this->user", "$this->password");
            if ( $this->CnMain) {
                return true;
            }else{
                $this->CnMain=null;
                return false;
                die("Ошибка авторизации пользователя $login. \n Описание ошибки:  $this->TxtException ");
            }
        } catch (PDOException $e) {
            return false;
            die("Ошибка подключения к базе данных. Описание ошибки:  $e \n");
        }
    }

    function Connect($login){
        $StrSql = iconv("utf-8", "cp1251","Select П.Пароль as psw, П.Соль as salt,Ф.UserName from Фамилии as Ф left join Stmain.dbo.ФамилииПароль as П on Ф.Код = П.КодФамилии Where Ф.email='$login'");
        try{
            foreach  ( $this->CnMain->query($StrSql) as $row){
                $this->CnMain=null;
                $this->user=$row['UserName'];
                $this->password =  $row['psw'] . substr($row['salt'],3,5);
                $this->CnMain = new PDO ("dblib:host=$this->host;dbname=$this->dbmain;charset=cp1251", $this->user,$this->password);
                return true;
                exit;
            }
            $this->TxtException.="Пользователь с логином $login не найден.";
            return false;
            exit;
        } catch (PDOException $e) {
            die("Ошибка авторизации. \n Описание ошибки: $e ");
            return false;
        }
    }

    private function PdoQuery_ToArray($InputArr){
        $StrFunction = new ExtendedStr();
        foreach ($InputArr as $key =>$row){
            foreach ( $row as $key=>$val) {
                $RetRow[$StrFunction->Str_ToUtf($key)] =$StrFunction->Str_ToUtf($val);
            }
            $RetArr[]=$RetRow;
        }
        return $RetArr;
    }

    function Query($StrSQL){
        try {
            $Query=$this->CnMain->query(iconv("utf-8", "cp1251",$StrSQL));
            $RetArr = DB::PdoQuery_ToArray($Query);
            return $RetArr;
        }catch (PDOException $e){
            die("Ошибка выполнения запроса: $StrSQL .  \n Описание ошибки: $e ");
        }
    }


}