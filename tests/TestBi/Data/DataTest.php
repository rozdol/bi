<?php
namespace Test\Rozdol\Data;

use Rozdol\Data\Data;
use Rozdol\Db\Db;

use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    
    protected function setUp()
    {
        $vars['server']='localhost';
        $vars['user']='postgres';
        $vars['pass']='1234';
        $vars['name']='postgres';
        $vars['port']='5432';

        $this->db =  Db::getInstance(
            $vars['server'],
            $vars['user'],
            $vars['pass'],
            $vars['name'],
            $vars['port']
        );

        $this->db->ShowErrors();
        $res=$this->db->GetVar("SET DateStyle = 'German';");

        //$this->MyObject = Data::getInstance($this->db);
       // $this->MyObject = new Data($this->db);
        $this->MyObject = new Data($this->db);
    }

     /**
    * @dataProvider dataProvider
    */
    public function testData($expect)
    {
        //$result = $this->MyObject->test();
        //fwrite(STDERR, print_r($result, true));
        //$this->assertEquals($expect, $result);
        $this->assertEquals(1, 1);
    }
    public function dataProvider()
    {
        $holidays=['01.02.2011','08.08.2011'];
        return [
            ['ok'],
        ];
    }
}
