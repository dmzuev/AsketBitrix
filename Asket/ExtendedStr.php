<?php

class  ExtendedStr{
    public function StrToUtf($Str){
        return iconv("cp1251","utf-8",$Str);
    }
}
?>