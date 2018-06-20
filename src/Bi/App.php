<?php
namespace Rozdol\App;

use Rozdol\Db\Db;
use Rozdol\Data\Data;
use Rozdol\Router\Router;

class App
{
    private static $hInstance;

    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new App();
        }
        return self::$hInstance;
    }

    public $db;
    protected $logic;
    public $data;
    public function __construct()
    {

        $this->db = DB::getInstance(
            $_ENV['DB_SERVER'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            $_ENV['DB_NAME'],
            $_ENV['DB_PORT']
        );
        $this->db->ShowErrors();
        $res=$this->db->GetVar("SET DateStyle = 'German';");


        date_default_timezone_set('Etc/GMT'); //Europe/Athens Etc/GMT
        ini_set('memory_limit', '-1');
        //$this->db->DebugOn();

        $this->data = Data::getInstance($this->db);
        $this->router = Router::getInstance($this->db);
    }

    public function run2()
    {
         echo $this->router->test();
    }
    public function run()
    {

        $GLOBALS['time_marker']['before_chkInstall']=round(microtime(true)-$GLOBALS['starttime'], 2);
        $this->data->chkInstall();
        $GLOBALS['time_marker']['after_chkInstall']=round(microtime(true)-$GLOBALS['starttime'], 2);
        $this->data->getDefVals();
        $GLOBALS['time_marker']['after_getDefVals']=round(microtime(true)-$GLOBALS['starttime'], 2);

        $this->data->auth();
        $this->data->click();
        $GLOBALS['time_marker']['after_auth']=round(microtime(true)-$GLOBALS['starttime'], 2);

        //if (ob_get_level() == 0) ob_start();
        $this->router->run();

        $GLOBALS['time_marker']['after_doAction']=round(microtime(true)-$GLOBALS['starttime'], 2);
        //ob_end_flush();
    }

    function test()
    {
        return "ok";
    }
    function test_echo()
    {
        echo 'TEST WORKS';
    }
}
