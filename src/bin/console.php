<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 12.
 * Time: 16:35
 */

try {
    $parsedInputs = parseConsoleArguments($argv);

} catch (Exception $e) {
    echo "\nERROR\n=====\n";
    echo $e->getMessage()."\n";
}

/**
 * @param array $argv
 * @return array
 * @throws Exception
 */
 function parseConsoleArguments($argv) {

    //console.php-t kiszedjük
    array_shift($argv);

    if(count($argv) < 1) {
        throw new \Exception("Missing task name.");
    }

    $name_parts = explode(":",array_shift($argv));
    if(!isset($name_parts[1])) {
        throw new \Exception("Syntax error at task name. Valid syntax [task_type]:[task_name]");
    }

    //Kiszedjük az összes paramétert, amit átadtak
    $params = [];
    for($i = 0; $i<count($argv); $i+=2) {
        if(strpos($argv[$i],"--") != 0) {
            throw new \Exception("Syntax error at: ".$argv[$i].".");
        }
        if(!isset($argv[$i+1])) {
            throw new \Exception("Missing value at ".$argv[$i].".");
        }
        $params[substr($argv[$i],2)] = $argv[$i+1];
    }

    //Bootstrap mindenképpen kell
    if(!isset($params["bootstrap"])) {
        throw new \Exception("Missing --bootstrap argument.");
    }

    return [
        "task_type" => $name_parts[0],
        "task_name" => $name_parts[1],
        "inputs"    => $params
    ];

}