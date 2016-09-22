<?php
use PandaBase\Connection\TableDescriptor;
use PandaBase\Record\SimpleRecord;

/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 09. 22.
 * Time: 11:20
 */
class JsonResponseTest extends PHPUnit_Framework_TestCase
{
    private $values;

    protected function setUp()
    {
        $this->values = [
            "rec1" =>   new TestRec(-1,[
                "asd"   =>  11,
                "er"    =>  43242
            ]),
            "rec2" => [
                new TestRec(-1,[
                    "asd"   =>  12,
                    "er"    =>  43242
                ]),
                new TestRec(-1,[
                    "asd"   =>  13,
                    "er"    =>  43242
                ])
            ],
        ];

    }

    public function testRecursiveArray() {
        $resp = new \KodiApp\Response\JsonResponse($this->values);
        $this->assertEquals(2,2);
    }
}

class TestRec extends SimpleRecord{
    /**
     * @param int $id
     * @param null $values
     */
    function __construct($id, $values = null)
    {
        $tableDescriptor = new TableDescriptor([
            TABLE_NAME  =>  "pp_simple_table",
            TABLE_ID    =>  "table_id",
        ]);
        parent::__construct($tableDescriptor,$id,$values);
    }

    function __toString()
    {
        return implode(",",$this->getAll());
    }


}