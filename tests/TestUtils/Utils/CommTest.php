<?php
namespace Test\Rozdol\Utils;

use Rozdol\Utils\Comm;

use PHPUnit\Framework\TestCase;

class CommTest extends TestCase
{
    
    protected function setUp()
    {
        $this->Comm = Comm::getInstance();
        //$this->Comm = new Comm();
    }

     /**
    * @dataProvider dataProvider
    */
    public function testComm($str, $expect)
    {
        $result = $this->Comm->test();
        $this->assertEquals($expect, $result);
    }
    public function dataProvider()
    {
        return [
            ['test','ok']
        ];
    }
}
