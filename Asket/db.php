<?php
class db{
    private $dbst = "st";
    private $dbmain = "stmain";
    private $user = "wwwOrders";
    private $password= "wwwOrders";
    private $host = "192.168.0.200:6000";

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
            //$this->CnMain = mssql_connect ($this->host, $this->user, $this->password) or die(mssql_get_last_message());
            if ( $this->CnMain) {
                return true;
            }else{
                $this->CnMain=null;
                echo "Ошибка авторизации пользователя $this->user. \n Описание ошибки:  $this->TxtException ";
                return false;
            }
        } catch (PDOException $e) {
            echo "Ошибка подключения к базе данных. Описание ошибки:  $e \n";
            return false;
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
                //$this->CnMain= mssql_connect ($this->Srv, $this->user,$this->password) or die(mssql_get_last_message());
                return true;
            }
            $this->TxtException.="Пользователь с логином $login не найден.";
            return false;
        } catch (PDOException $e) {
            echo "Ошибка авторизации. \n Описание ошибки: $e ";
            return false;
        }
    }

    private function PdoQuery_ToArray($InputArr){
        $StrFunction = new extendedStr();
        foreach ($InputArr as $key =>$row){
            foreach ( $row as $key=>$val) {
                $RetRow[$StrFunction->strToUTF($key)] =$StrFunction->strToUTF($val);
            }
            $RetArr[]=$RetRow;
        }
        return $RetArr;
    }
    private  function MsSQLQuery_ToArray($Query){
        $StrFunction = new extendedStr();
        while ($rows = mssql_fetch_array($Query)) {
            foreach ($rows as $key =>$row){
                $RetRow[$StrFunction->strToUtf($key)] =$StrFunction->strToUTF($row);
            }
            $RetArr[]=$RetRow;
        }
        return $RetArr;
    }

    function Query($StrSQL){
        try {
            $Query=$this->CnMain->query(iconv("utf-8", "cp1251",$StrSQL));
            $RetArr = DB::PdoQuery_ToArray($Query);
            //$Query = mssql_query($StrSQL,$this->CnMain);
            //$RetArr= DB::MsSQLQuery_ToArray($Query);
            return $RetArr;
        }catch (PDOException $e){
            echo "Ошибка выполнения запроса: $StrSQL .  \n Описание ошибки: $e ";
            return false;
        }
    }


}