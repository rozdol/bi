<?php
namespace Test\Rozdol\Dates;

use Rozdol\Dates\Dates;

use PHPUnit\Framework\TestCase;

class DatesTest extends TestCase
{
    
    protected function setUp()
    {
        $this->dates = Dates::getInstance();
        //$this->dates = new Dates();
    }

    public function testDate()
    {
        $correct_date = $this->dates->F_date('01/01/20', 1);
        $this->assertEquals('01.01.2020', $correct_date);


        $US_formatted_date = $this->dates->F_USdate('31.03.2020', 1);
        $this->assertEquals('2020-03-31', $US_formatted_date);


        $correct_date = $this->dates->F_date($US_formatted_date, 1);
        $this->assertEquals('31.03.2020', $correct_date);


        $MS_Excell_date = $this->dates->F_date2xls($correct_date, 1);
        $this->assertEquals('43921', $MS_Excell_date);

        $correct_date = $this->dates->F_xls_date($MS_Excell_date, 1);
        $this->assertEquals('31.03.2020', $correct_date);
    }

    /**
    * @dataProvider ntnWorkingDatesProvider
    */
    public function testGetNonWorkingDays($start_date = '', $end_date = '', $holidays = array(), $expect)
    {
        $result = $this->dates->getNonWorkingDays($start_date, $end_date, $holidays);
        $this->assertEquals($expect, $result);
    }
    public function ntnWorkingDatesProvider()
    {
        $holidays=['01.02.2011','08.08.2011'];
        return [
            ['01.01.2011','31.12.2011',$holidays,107],
            ['01.01.2017','31.12.2017',[],105],
            ['01.04.2018','31.04.2018',[],9],
        ];
    }

    /**
    * @dataProvider workingDatesProvider
    */
    public function testGetWorkingDays($start_date = '', $end_date = '', $holidays = array(), $expect)
    {
        $result = $this->dates->get_working_days($start_date, $end_date, $holidays);
        $this->assertEquals($expect, $result);
    }
    public function workingDatesProvider()
    {
        $holidays=['01.02.2011','08.08.2011'];
        return [
            ['01.01.2011','31.12.2011',$holidays,(365-52*2-12-2)],
            ['01.01.2017','31.12.2017',[],(365-52*2-12)],
        ];
    }



    /**
    * @dataProvider randeDatesProvider
    */
    public function testIsInDaterange($date = '', $date1 = '', $date2 = '', $include_today = 0, $expect)
    {
        $result = $this->dates->is_indaterange($date, $date1, $date2, $include_today);
        $this->assertEquals($expect, $result);
    }
    public function randeDatesProvider()
    {
        return [
            ['15.01.2011','01.01.2011','31.01.2011',1,1],
            ['15.01.2011','15.01.2011','15.01.2011',1,1],
            ['15.01.2011','15.01.2011','15.01.2011',0,0],
            ['15.01.2011','16.01.2011','31.01.2011',1,0],

        ];
    }


    /**
    * @dataProvider datesProvider
    */
    public function testDateNext($start_date, $date, $freq, $base = '30/360', $expect)
    {
        $result = $this->dates->F_datenext($start_date, $date, $freq, $base = '30/360');
        $this->assertEquals($expect, $result);
    }
    public function datesProvider()
    {
        return [
            ['01.01.2011','15.01.2011',12,'30/360','31.01.2011'],
            ['01.01.2011','20.04.2011',1,'30/360','01.01.2012'],
        ];
    }
}
