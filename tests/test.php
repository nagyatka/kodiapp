<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2017. 04. 06.
 * Time: 0:23
 */

function isAssoc(array $arr)
{
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

var_dump(isAssoc(array('a', 'b', 'c'))); // false
var_dump(isAssoc(array("0" => 'a', "1" => 'b', "2" => 'c'))); // false
var_dump(isAssoc(array("1" => 'a', "0" => 'b', "2" => 'c'))); // true
var_dump(isAssoc(array("a" => 'a', "b" => 'b', "c" => 'c'))); // true
var_dump(isAssoc([
    [
        "name"      =>  "database_connection", //Panabasen belüli azonosító
        "driver"    =>  "mysql",
        "charset"   =>  "utf8",
    ],
    [
        "name"      =>  "database_connection", //Panabasen belüli azonosító
        "driver"    =>  "mysql",
        "charset"   =>  "utf8",
    ],
]));