<?php
namespace Test\Rozdol;

use Rozdol\Utils\Utils;

use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    
    protected function setUp()
    {
        $this->utils = Utils::getInstance();
        //$this->utils = new Utils();
    }

     /**
    * @dataProvider dataProvider
    */
    public function testUtils($str, $instr, $expect)
    {
        $correct_date = $this->utils->contains($str, $instr);
        $this->assertEquals($expect, $correct_date);
    }
    public function dataProvider()
    {
        return [
            ['token ','token in the string',1],
            [' token','token in the string',0],
        ];
    }
}
