<?php
namespace Test\Rozdol\Html;

use Rozdol\Html\Html;

use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    
    protected function setUp()
    {
        $this->html = Html::getInstance();
        //$this->html = new Html();
    }
    public function testHtml()
    {
        $result = $this->html->tablehead();
        $this->assertContains('<table', $result);

        $result = $this->html->money('.134134');
        $this->assertEquals('0.13', $result);

        $result = $this->html->money('123423.135134');
        $this->assertEquals('123 423.14', $result);
    }

    /**
    * @dataProvider moneyProvider
    */
    public function testMoney($sum = 0, $curr = '', $opt = '', $dec = 2, $expect)
    {


        $result = $this->html->money($sum, $curr, $opt, $dec);
        $this->assertEquals($expect, $result);
    }
    public function moneyProvider()
    {
        return [
            ['.23452','','',2,'0.23'],
            ['.23452','','',3,'0.235'],
            ['11234.23452','','',2,'11 234.23'],
            ['11234.23452','EUR','',2,'11 234.23 EUR'],
            ['-11234.23452','EUR','',2,'-11 234.23 EUR'],
            ['-11234.23452','EUR','warn_negative',2,"<span class='badge badge-important' id=''>-11 234.23 EUR</span>"],
        ];
    }
}
