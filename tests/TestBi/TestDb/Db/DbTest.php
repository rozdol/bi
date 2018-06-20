<?php
namespace Test\Rozdol\Db\Db;

use Rozdol\Db\Db;

use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{
    
    protected function setUp()
    {
        //$this->db = Db::getInstance();
        //$this->db = new Db();

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
    }
    public function testConn()
    {
        $test_table="test_table_234233";
        $sql_create="
        CREATE TABLE $test_table(
            id serial NOT NULL,
            text_field text,
            CONSTRAINT ${test_table}_pkey PRIMARY KEY (id)
        );
        ";

        $sql_check="SELECT * from information_schema.tables where table_schema = 'public' and table_name='$test_table'";
        $sql_insert="INSERT INTO $test_table (text_field) VALUES ('some test')";
        $sql_select="SELECT text_field from $test_table where id=1";
        $sql_drop="DROP TABLE $test_table;";

        //Check if testing table exists. Result must be null
        $result=$this->db->GetResults($sql_check);
        $result=$this->db->GetRow($sql_check);
        $this->assertEquals(null, $result[table_name]);

        //Create atesting table
        $this->db->GetResults($sql_create);
        $result=$this->db->GetRow($sql_check);
        $this->assertEquals($test_table, $result[table_name]);

        //Insert some value
        $this->db->GetResults($sql_insert);
        //Check id it exits
        $result=$this->db->getval($sql_select);
        $this->assertEquals('some test', $result);


        //Drop testing table
        $this->db->GetResults($sql_drop);
        $result=$this->db->GetRow($sql_check);
        $this->assertEquals(null, $result[table_name]);

        //fwrite(STDERR, "RES:".print_r($result, true));


        //$this->db->ExecQuery($sql_create);
    }
}
