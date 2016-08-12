<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 12.
 * Time: 12:43
 */

$url = "/asd/{ddd:[a-z]+}/{kuka}/asd/{tojci}";
$params = [
    "ddd"   =>  "dfwe",
    //"kuka"  =>  "szemetes",
    "tojci" =>  "sssss"
];

echo generate($url,$params)."\n";
/*
// Performance test
$res = [];
for($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    generate($url,$params);
    $end = microtime(true)-$start;
    $res[] = $end;
    echo $end."\n";
}
echo "AVG: ".(array_sum($res)/count($res)*1000)." millisec \n";

// Result: AVG: 0.039803981781006 millisec
*/


function generate($url,$params) {
    $resultUrl = "";
    $index = 0;
    while(($firstPos = strpos($url,"{",$index)) != false) {
        $lastPos = strpos($url,"}",$firstPos);
        $name = explode(":",substr($url,$firstPos+1,($lastPos-$firstPos-1)))[0];
        $resultUrl .= substr($url,$index,$firstPos-$index).(isset($params[$name])?$params[$name]:"");
        $index = $lastPos+1;
    };
    return $resultUrl;
}

