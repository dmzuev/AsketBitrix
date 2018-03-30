<?php
namespace Asket;
class  ExtendedStr{
    public function Str_ToUtf($Str){
        return iconv("cp1251","utf-8",$Str);
    }
}
?>