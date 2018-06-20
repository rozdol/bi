<?php
namespace Test\Rozdol\App;

use Rozdol\App\App;

use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    
    protected function setUp()
    {
        //$this->MyObject = App::getInstance();
        // $GLOBALS['db']['server']='localhost';
        // $GLOBALS['db']['user']='postgres';
        // $GLOBALS['db']['pass']='1234';
        // $GLOBALS['db']['name']='postgres';
        // $GLOBALS['db']['port']='5432';

        $_ENV['DB_SERVER']='localhost';
        $_ENV['DB_USER']='postgres';
        $_ENV['DB_PASS']='1234';
        $_ENV['DB_NAME']='postgres';
        $_ENV['DB_PORT']='5432';

        $this->MyObject = new App();
    }

     /**
    * @dataProvider dataProvider
    */
    public function testApp($expect)
    {
        $result = $this->MyObject->test();
        //fwrite(STDERR, print_r($result, true));
        $this->assertEquals($expect, $result);
    }
    public function dataProvider()
    {
        $holidays=['01.02.2011','08.08.2011'];
        return [
            ['ok'],
        ];
    }
}
