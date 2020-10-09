<?php
namespace Rozdol\Dates;

use DateTime;
use DateTimeZone;

class Dates
{
    private static $hInstance;

    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new Dates();
        }
        return self::$hInstance;
    }

    public function cleandate($date = '')
    {
        $date=str_ireplace("/", ".", $date);
        $date=str_ireplace("-", ".", $date);
        return $date;
    }
    public function F_extractyear($date = '')
    {
            list($day,$month,$year) = explode('.', $date);
          return $year;
    }
    public function F_extractmonth($date = '')
    {
            list($day,$month,$year) = explode('.', $date);
          return $month;
    }
    public function F_extractmonthyaer($date = '')
    {
            list($day,$month,$year) = explode('.', $date);
            $month=$month*1;
            $month=strtoupper($GLOBALS[Monthes][$month]);
            $year=substr($year, 2, 2);
            $res="{$month}{$year}";
          return $res;
    }
    public function F_extractday($date = '')
    {
            list($day,$month,$year) = explode('.', $date);
          return $day;
    }
    public function F_leapdays($date1 = '', $date2 = '')
    {
        $res=0;
        return $res;
    }
    public function F_date2xls($date = '')
    {
        $UNIX_DATE=strtotime($this->F_date($date), 1);
        $EXCEL_DATE = 25569 + ($UNIX_DATE / 86400);
        return $EXCEL_DATE;
    }
    public function F_xls_date($EXCEL_DATE = 0)
    {
        $UNIX_DATE = ($EXCEL_DATE - 25569) * 86400;
        return gmdate("d.m.Y", $UNIX_DATE);
    }
    public function F_xls_datetime($UNIX_DATE = 0)
    {
        $UNIX_DATE = ($EXCEL_DATE - 25569) * 86400;
        return gmdate("d.m.Y H:i:s", $UNIX_DATE);
    }

    public function is_date2($string = '')
    {
        return ((DateTime::createFromFormat('Y-m-d', $string)||(DateTime::createFromFormat('d.m.Y', $string))) !== false);
    }

    public function is_date($input = '')
    {
        $formats = array("d.m.Y", "d/m/Y","Y-m-d"); // and so on.....
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $input);
            if ($date == false) {
                return false;
            } else {
                return true;
            }
        }
    }
    public function F_datediffmonths($date1 = '', $date2 = '')
    {

        //echo "RES:<br>$date1,$date2<br>";
        if ($date1=='') {
            $date1=time();
            $date1=date('d.m.Y', $date1);
        }
        list($day1,$month1,$year1) = explode('.', $date1);
        $date1 = "{$month1}/{$day1}/{$year1}";
        $date1 = "{$year1}-{$month1}-{$day1}";

        if ($date2=='') {
            $date2=time();
            $date2=date('d.m.Y', $date2);
        }
        list($day2,$month2,$year2) = explode('.', $date2);
        $date2 = "{$year2}-{$month2}-{$day2}";

        $d1 = strtotime($date1);
        $d2 = strtotime($date2);


        $dd1  = date('d.m.Y', $d1);
        $dd2  = date('d.m.Y', $d2);
        //echo "$dd1,$dd2<br>";

        $min_date = $d1;
        $max_date = $d2;
        $i = 0;
        $mdd  = date('d.m.Y', $max_date);

        while (($min_date = strtotime("+1 MONTH", $min_date)) <= $max_date) {
            $i++;
            $dd  = date('d.m.Y', $min_date);
            //echo "$i DD:$dd <= $mdd<br>";
        }

        return $i;
    }
    public function F_timediff($date1 = '', $date2 = '')
    {
        if ($date1=='') {
            $date1=time();
            $date1=date('d.m.Y H:i:s', $date1);
        }
        if ($date2=='') {
            $date2=time();
            $date2=date('d.m.Y H:i:s', $date2);
        }
        $date1 = strtotime($date1);
        $date2 = strtotime($date2);
        $res = round(($date2 - $date1), 0);
        $secinday=(60*60*24);
        $secinhour=(60*60);
        $days=floor($res/$secinday);
        $rest=$res-$days*$secinday;
        $hours=floor($rest/$secinhour);
        $rest=$rest-$hours*$secinhour;
        $minutes=floor($rest/60);
        //return "$days days $hours hours $minutes minutes";
        return $days."D ".$hours."H ".$minutes."M";
    }
    public function F_datenext($start_date = '', $date = '', $freq, $base = '30/360')
    {
        if ($freq==0) {
            $freq=1;
        }
        $start_date=$this->F_date($start_date, 1);
        $date=$this->F_date($date, 1);
        $days=$this->F_datediff($start_date, $date, $base);
        $days_in_preriod=360/$freq;
        $periods=$days/$days_in_preriod;
        $periods_dec=floor($periods);
        $periods_frac=$periods-$periods_dec;
        $days_till_end=round($periods_frac*$days_in_preriod, 0);
        $days_from_start=($periods_dec+1)*$days_in_preriod;
        if ($days_from_start>360) {
            $years=floor($days_from_start/360);
            $next_date1=$this->F_dateadd_year($start_date, $years);
            $next_date=$this->F_datenext($next_date1, $date, $freq, $base);
        } else {
            if ($days_from_start>30) {
                $months=floor($days_from_start/30);
                $next_date2=$this->F_dateadd_month($start_date, $months);
                $next_date=$this->F_datenext($next_date2, $date, $freq, $base);
            } else {
                $next_date=$this->F_dateadd($start_date, $days_from_start);
            }
        }

        return $next_date;
    }
    public function is_indaterange($date = '', $date1 = '', $date2 = '', $include_today = 0)
    {
        return (($this->is_later($date, $date1, $include_today))&&($this->is_earlier($date, $date2, $include_today)));
    }
    public function is_later($date1, $date2, $include_today = 0)
    {
        $days=$this->F_datediff($date1, $date2);
        if ($include_today==0) {
            if ($days<0) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($days<=0) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function is_earlier($date1 = '', $date2 = '', $include_today = 0)
    {
        $days=$this->F_datediff($date1, $date2);
        if ($include_today==0) {
            if ($days>0) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($days>=0) {
                return true;
            } else {
                return false;
            }
        }
    }
    public function is_sameday($date1 = '', $date2 = '')
    {
        $days=$this->F_datediff($date1, $date2);
        if ($days==0) {
            return true;
        } else {
            return false;
        }
    }

    public function F_datediff($date1 = '', $date2 = '', $base = '')
    {
        if ($date1=='') {
            $date1=time();
            $date1=date('d.m.Y', $date1);
        }
        list($day1,$month1,$year1) = explode('.', $date1);
        $date1 = "{$month1}/{$day1}/{$year1}";
        $date1 = strtotime($date1);

        if ($date2=='') {
            $date2=time();
            $date2=date('d.m.Y', $date2);
        }
        list($day2,$month2,$year2) = explode('.', $date2);
        $date2 = "{$month2}/{$day2}/{$year2}";
        $date2 = strtotime($date2);
        if (($base=='')||($base=='365')||($base=='A/360'||($base=='Actual/360'))) {
            $res = round(($date2 - $date1)/(3600*24), 0);
        }
        if ($base=='30/360') {
            /*
            Разница в днях N между двумя датами T1 и T2 расчитывается как выражение:

            N = D2 — D1 + 30 (M2 — M1) + 360 (Y2 — Y1), где

            D1/M1/Y1 — дата T1 (первая дата)
            D2/M2/Y2 — дата T2 (вторая дата)
            Существует три варианта базиса 30/360.

            30/360
            Если D1 приходится на 31 число, D1 меняется на 30.
            Если D2 приходится на 31 число, D2 меняется на 30, только если D1 приходится на 30 или 31 числа.

            30E/360
            Если D1 приходится на 31 число, D1 меняется на 30.
            Если D2 приходится на 31 число, D2 меняется на 30.

            30E+/360
            Если D1 приходится на 31 число, D1 меняется на 30.
            Если D2 приходится на 31 число, D2 меняется на 1 и М2 увеличивается на единицу.

            0 or omitted    US (NASD) 30/360
            1                Actual/actual
            2                Actual/360
            3                Actual/365
            4                European 30/360

            */
            if ($day1==31) {
                $day1=30;
            }
            if (($day2==31)&&($day1==30)) {
                $day2=30;
            }


            $res=$day2-$day1 + 30*($month2-$month1) +360*($year2-$year1);
        }
        if ($base=='30E/360') {
            if ($day1==31) {
                $day1=30;
            }
            if ($day2==31) {
                $day2=30;
            }


            $res=$day2-$day1 + 30*($month2-$month1) +360*($year2-$year1);
        }
        if ($base=='30E+/360') {
            if ($day1==31) {
                $day1=30;
            }
            if ($day2==31) {
                $day2=1;
                $month2=$month2+1;
                if ($month2>12) {
                    $month2=12;
                    $year2=$year2+1;
                }
            }

            $res=$day2-$day1 + 30*($month2-$month1) +360*($year2-$year1);
        }
        return $res;
    }
    public function F_dates($string = '')
    {
        $string=strtolower($string);
        if (strpos('q', $string) !== false) {
            $tokens=explode('q', $string);
            echo $this->pre_display($tokens, "tokens $string");
            if (!$tokens[0]) {
                $year=$this->F_thisyear();
            } else {
                if ($tokens[0]<100) {
                    $year="20$tokens[0]";
                } else {
                    $year=$tokens[0];
                }
            }
        } else {//months
            $GLOBALS[monthes]=array_map('strtolower', $GLOBALS[Monthes]);
        }
        $date=$year;
        return $date;
    }

    public function F_date_reset($date = '')
    {
        if (($date=='01.01.2030')or($date=='01.01.1970')) {
            $date='';
        }
        return $date;
    }
    public function F_date($str = '', $check = '', $delim = '')
    {
        //echo "$str,$check,$delim";
        $str=str_ireplace("'", '', $str);
        $str=str_ireplace(',', '.', $str);
        $str=str_ireplace(' ', '.', $str);
        $str=str_ireplace('\\', '.', $str);
        $str=str_ireplace('/', '.', $str);
        $today = time();

        $tz = 'Europe/Nicosia';
        $timestamp = time();
        $dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
        date_timezone_set($tz);
        $dt->setTimestamp($timestamp); //adjust the object to correct timestamp
        //echo $dt->format('d.m.Y, H:i:s');

        // $year  = date('.Y',$today);
        // $yearmonth  = date('.m.Y',$today);

        $year  =$dt->format('.Y');
        $yearmonth  =$dt->format('.m.Y');

        if ((strlen($str)<1) && ($check==1)) {
            //$str=date('d.m.Y',$today);
            $str=$dt->format('d.m.Y');
        }
        //if ((strlen($str)<1) && ($check==2)) {$str='01.01.2030'; }
        if (strlen($str)>0) {
            if (strlen($str)<3) {
                $str=$str.$yearmonth;
            }
            if (strlen($str)<6) {
                $str=$str.$year;
            }
            if (strlen($str)<10) {
                $str=substr($str, 0, 6)."20".substr($str, -2, 2);
            }

            $str=strtotime($str);

            $str  = date('d.m.Y', $str);
        }
        if ($delim<>'') {
            $str=str_ireplace('.', '/', $str);
        }
        if (($str=='')&&($check==2)) { //set null
            $str='null';
        }
        if (($str=='')&&($check==3)) { //set latest
            $str='01.01.2030';
        }
        if (($str=='')&&($check==4)) {
            $str='01.01.'.$this->F_thisyear(); //set beginning of the current year
        }
        if (($str=='')&&($check==5)) { //set earliest
            $str='01.01.1999';
        }
        if (($str=='')&&($check==6)) { // set now
            $str='now()';
        }
        if (($str=='')&&($check==7)) { //set null
            $str='null';
        }
        if (($str=='')&&($check==8)) { //set end of the current year
            $str='31.12.'.$this->F_thisyear();
        }
        if (($str=='')&&($check==9)) { //set beginning of the current month
            $str='01.'.$this->F_thismonth().'.'.$this->F_thisyear();
        }
        if (($str=='')&&($check==10)) { //set end of the current month
            $str=$this->F_daysinmonth('').'.'.$this->F_thismonth().'.'.$this->F_thisyear();
        }
        if (($str!='now()')&&($check==6)) {
            $str="'$str'";
        }
        if (($str!='null')&&($check==7)) {
            $str="'$str'";
        }

        return $str;
    }
    public function easter($year = 0)
    {
        /*
        $a = $year % 19;
        $b = (int) ($year / 100);
        $c = $year % 100;
        $d = (int) ($b / 4);
        $e = $b % 4;
        $f = (int) (($b + 8) / 25);
        $g = (int) (($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = (int) ($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = (int) (($a + 11 * $h + 22 * $l) / 451);
        $p = ($h + $l - 7 * $m + 114) % 31;

        $month = (int) (($h + $l - 7 * $m + 114) / 31);
        $day = $p + 1;
        $date=$this->F_date(sprintf("%02s",$day).'.'.sprintf("%02s",$month).'.'.$year);
        */

        $a = $year % 4;
            $b = $year % 7;
            $c = $year % 19;
            $d = (19 * $c + 15) % 30;
            $e = (2 * $a + 4 * $b - $d + 34) % 7;
            $month = floor(($d + $e + 114) / 31);
            $day = (($d + $e + 114) % 31) + 1;

            $de = mktime(0, 0, 0, $month, $day + 13, $year);

        $date= date('d.m.Y', $de);

        return $date;
    }
    public function pre_display($text = '', $title = '', $class = '')
    {
        if ($title!='') {
            $out.= "<h3>$title</h3>";
        }
        $out.="<pre>";
        $out.=print_r($text, true);
        $out.= "</pre>";
        if ($class!='') {
            $out.= "<div class='alert $class'>$out</div>";
        }
        return $out;
    }

    public function holidays($year)
    {
        $strictholidays=array(
        "01.01.$year", //New year
        "06.01.$year", //Epifany
        "25.03.$year", //Geek Independance
        "01.04.$year", //Greek Cypriot National Day
        "01.05.$year", //Labour Day
        "15.08.$year", //Assumption
        "01.10.$year", //Cyprus Independence Day
        "28.10.$year", //Greek National Day (Ochi Day)
        "25.12.$year", //Christmas Eve
        "25.12.$year", //Christmas
        "26.12.$year", //2nd day Christmas
        "26.12.$year" //New Year Eve
        );

        $GLOBALS[settings][holidays]=str_ireplace(';',',',$GLOBALS[settings][holidays]);
        $extra_holidays_tmp=array_map('trim', explode(',',$GLOBALS[settings][holidays]));
        foreach ($extra_holidays_tmp as $day) {
            if (strpos($day, strval($year)) !== false)$extra_holidays[]=$day;
        }

        $date=$this->easter($year);
        $easter_org=$date;

        $easter=$easter_org;
        $easter_mo=$this->F_dateadd($easter_org, 1);
        $easter_tu=$this->F_dateadd($easter_org, 2);
        $easter_fr=$this->F_dateadd($easter_org, -2);
        $easter_pc=$this->F_dateadd($easter_org, +50);
        $easter_gr=$this->F_dateadd($easter_org, -48);

        $movingholidays=array(
            "$easter_gr", //Green Monday
            "$easter_fr", //Greek Orthodox Good Friday
            "$easter", //Greek Orthodox Easter
            "$easter_mo", //Greek Orthodox Easter Monday
            "$easter_tu", //Greek Orthodox Easter Tuesday
            "$easter_pc" //Pentecost (Kataklysmos)
        );
        /*
        if($year==2009)
        $movingholidays=array(
            "02.03.$year", //Green Monday
            "17.04.$year", //Greek Orthodox Good Friday
            "20.04.$year", //Greek Orthodox Easter Monday
            "21.04.$year", //Greek Orthodox Easter Tuesday
            "08.06.$year" //Pentecost (Kataklysmos)
        );
        if($year==2010)
        $movingholidays=array(
            "15.02.$year", //Green Monday
            "02.04.$year", //Greek Orthodox Good Friday
            "05.04.$year", //Greek Orthodox Easter Monday
            "06.04.$year", //Greek Orthodox Easter Tuesday
            "24.05.$year" //Pentecost (Kataklysmos)
        );
        if($year==2011)
        $movingholidays=array(
            "07.01.$year", //Bank Holiday
            "07.03.$year", //Green Monday
            "22.04.$year", //Greek Orthodox Good Friday
            "25.04.$year", //Greek Orthodox Easter Monday
            "26.04.$year", //Greek Orthodox Easter Tuesday
            "12.05.$year" //Pentecost (Kataklysmos)
        );
        if($year==2012)
        $movingholidays=array(
            "27.02.$year", //Green Monday
            "13.04.$year", //Greek Orthodox Good Friday
            "16.04.$year", //Greek Orthodox Easter Monday
            "17.04.$year", //Greek Orthodox Easter Tuesday
            "03.06.$year" //Pentecost (Kataklysmos)
        );
        if($year==2013)
        $movingholidays=array(
            "18.03.$year", //Green Monday
            "03.05.$year", //Greek Orthodox Good Friday
            "06.05.$year", //Greek Orthodox Easter Monday
            "07.05.$year", //Greek Orthodox Easter Tuesday
            "24.06.$year" //Pentecost (Kataklysmos)
        );
        if($year==2014)
        $movingholidays=array(
            "03.03.$year", //Green Monday
            "18.04.$year", //Greek Orthodox Good Friday
            "21.04.$year", //Greek Orthodox Easter Monday
            "22.04.$year", //Greek Orthodox Easter Tuesday
            "09.06.$year" //Pentecost (Kataklysmos)
        );
        */

        if(!$strictholidays)$strictholidays=[];
        if(!$movingholidays)$movingholidays=[];
        if(!$extra_holidays)$extra_holidays=[];
        $holidays=array_merge($strictholidays, $movingholidays,$extra_holidays);
        //sort($holidays);
        //$str=implode("<br>",$holidays);
        // echo $this->pre_display($strictholidays,"S:$year");
        // echo $this->pre_display($movingholidays,"M:$year");
        // echo $this->pre_display($extra_holidays,"E:$year");
        // echo $this->pre_display($holidays,"H$year");
        return $holidays;
    }

    public function get_working_days($start_date = '', $end_date = '', $holidays = array())
    {

        $this_year=$this->F_thisyear();


        $holidays_arr=range(2009, $this_year+2);

        foreach ($holidays_arr as $year) {
            $holidays_this=$this->holidays($year);

            $holidays=array_merge($holidays, $holidays_this);
        }

        //echo $this->pre_display($holidays); exit;

        $start_ts = strtotime($start_date);
        $end_ts = strtotime($end_date);
        //foreach ($holidays as & $holiday) {$holiday = strtotime($holiday);}
        $working_days = 0;
        $tmp_ts = $start_ts;
        $tmp_date=$start_date;

        while ($this->F_datediff($tmp_date, $end_date)>-1) {
            $tmp_day = $this->F_weekday($tmp_date);
            if (($tmp_day < 6) && !in_array($tmp_date, $holidays)) {
                $working_days++;
                $dates_work[]=$tmp_date;
            } else {
                $dates_off[]=$tmp_date;
            }
            //echo "$tmp_date<br>";
            $tmp_date=$this->F_dateadd($tmp_date, 1);
        }
        //echo $this->pre_display($dates_work);
        //echo $this->pre_display($dates_off).$working_days;
        //exit;
        return $working_days;
    }
    public function days_in_month($month = '', $year = '')
    {
        if (empty($month)) {
                $date = $this->F_date("$month", 1);
                $date_p=strtotime($date);
                $month = date('m', $date_p);
                $year = date('Y', $date_p);
        }
        return date('t', mktime(0, 0, 0, $month+1, 0, $year));
    }
    public function days_in_month_date($date = '')
    {
                $date = $this->F_date($date, 1);
                $date_p=strtotime($date);
                $month = date('m', $date_p);
                $year = date('Y', $date_p);
        return date('t', mktime(0, 0, 0, $month+1, 0, $year));
    }
    public function lastday_in_month($date = '')
    {
        if (empty($date)) {
              $date = $this->F_date("", 1);
        }
           $date_p=strtotime($date);
           $month = date('m', $date_p);
           $year = date('Y', $date_p);
           $result = strtotime("{$year}-{$month}-01");
           $result = strtotime('-1 second', strtotime('+1 month', $result));
           return date('d.m.Y', $result);
    }
    public function firstday_in_month($date = '')
    {
        if (empty($date)) {
              $date = $this->F_date("", 1);
        }
           $date_p=strtotime($date);
           $month = date('m', $date_p);
           $year = date('Y', $date_p);
           $result = strtotime("{$year}-{$month}-01");
           //$result = strtotime('-1 second', strtotime('+1 month', $result));
           return date('d.m.Y', $result);
    }
    public function monthname($date = '')
    {
        if (empty($date)) {
              $date = F_date("", 1);
        }
           $date_p=strtotime($date);
           $month = date('m', $date_p);
           $year = date('Y', $date_p);
           $result = strtotime("{$year}-{$month}-01");
           return date('F', $result);
    }
    public function F_thisyear()
    {
        $today = time();
        $str  = date('Y', $today);
        return $str;
    }
    public function F_thismonth($frac = 0)
    {
        $today = time();
        $str  = date('m', $today);
        if ($frac>0) {
            $str--;
            $day  = date('j', $today);
            $days  = date('t', $today);
            $frac=$day/$days;
            $str=$str+$frac;
        }
        return $str;
    }
    public function F_thisday()
    {
        $today = time();
        $str  = date('z', $today);
        return $str;
    }
    public function F_thisweek()
    {
        $today = time();
        $str  = date('w', $today)*-1+1;
        $str =$this->F_dateadd($this->F_date("", 1), $str);
        return $str;
    }
    public function F_repdate($date = '')
    {
          $res=(substr($date, 0, 2).".".substr($date, 2, 2).".20".substr($date, 4, 2));
        return $res;
    }
    public function F_convdate($date = '')
    {
        if (!strpos($date, ".")) {
            $res=$this->F_date(substr($date, 6, 2).".".substr($date, 4, 2).".".substr($date, 0, 4));
        } else {
            $res=$date;
        }
        return $res;
    }
    public function F_MDconvdate($date = '')
    {
         $res=$this->F_date(substr($date, 3, 2).".".substr($date, 0, 2).".".substr($date, 6, 4));
        return $res;
    }
    public function F_USdate($date = '')
    {
        $date=str_replace('/', '.', $date);
        $date=str_replace('-', '.', $date);
        $arr=explode('.', $date);
        $res=sprintf('%04d', $arr[2]).'-'.sprintf('%02d', $arr[1]).'-'.sprintf('%02d', $arr[0]);
        return $res;
    }
    public function F_YMDate($date = '')
    {
        $res=(substr($date, 6, 4).substr($date, 3, 2).substr($date, 0, 2));
        return $res;
    }
    public function F_MDYDate($date = '', $delim='.', $short=0)
    {
        $date=str_replace('/', '.', $date);
        $arr=explode('.', $date);
        if($short>0)$arr[2]=substr($arr[2],2,2);
        $res=sprintf('%02d', $arr[1]).$delim.sprintf('%02d', $arr[0]).$delim.sprintf('%02d', $arr[2]);

        return $res;
    }


    public function getNonWorkingDays($startDate, $endDate, $holidays)
    {
        // do strtotime calculations just once
        $endDate = strtotime($endDate);
        $startDate = strtotime($startDate);
        //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
        //We add one to inlude both dates in the interval.
        $days = ($endDate - $startDate) / 86400 + 1;

        $no_full_weeks = floor($days / 7);
        $NonWorkingDays = $no_full_weeks * 2;
        //$no_remaining_days = fmod($days, 7);

        //It will return 1 if it's Monday,.. ,7 for Sunday
        $the_first_day_of_week = date("N", $startDate);
        $the_last_day_of_week = date("N", $endDate);

        //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
        //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
        if ($the_first_day_of_week <= $the_last_day_of_week) {
            if ($the_first_day_of_week >= 6 && 6 >= $the_last_day_of_week) {
                $NonWorkingDays++;
            }
            if ($the_first_day_of_week >= 7 && 7 >= $the_last_day_of_week) {
                $NonWorkingDays++;
            }
        } else {
            // the day of the week for start is later than the day of the week for end
            if ($the_first_day_of_week == 7) {
                // if the start date is a Sunday, then we definitely subtract 1 day
                $NonWorkingDays++;
                if ($the_last_day_of_week == 6) {
                    // if the end date is a Saturday, then we subtract another day
                    $NonWorkingDays++;
                }
            } else {
                // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
                // so we skip an entire weekend and subtract 2 days
                $no_remaining_days += 2;
            }
        }


        //We subtract the holidays
        foreach ($holidays as $holiday) {
            $time_stamp=strtotime($holiday);
            //If the holiday doesn't fall in weekend
            if ($startDate <= $time_stamp && $time_stamp <= $endDate && date("N", $time_stamp) != 6 && date("N", $time_stamp) != 7) {
                $NonWorkingDays++;
            }
        }

        return $NonWorkingDays;
    }

    public function getWorkingDays($startDate='', $endDate='', $holidays=[])
    {
        // do strtotime calculations just once
        $endDate = strtotime($endDate);
        $startDate = strtotime($startDate);
        //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
        //We add one to inlude both dates in the interval.
        $days = ($endDate - $startDate) / 86400 + 1;

        $no_full_weeks = floor($days / 7);
        $no_remaining_days = fmod($days, 7);

        //It will return 1 if it's Monday,.. ,7 for Sunday
        $the_first_day_of_week = date("N", $startDate);
        $the_last_day_of_week = date("N", $endDate);

        //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
        //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
        if ($the_first_day_of_week <= $the_last_day_of_week) {
            if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) {
                $no_remaining_days--;
            }
            if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) {
                $no_remaining_days--;
            }
        } else {
            // (edit by Tokes to fix an edge case where the start day was a Sunday
            // and the end day was NOT a Saturday)

            // the day of the week for start is later than the day of the week for end
            if ($the_first_day_of_week == 7) {
                // if the start date is a Sunday, then we definitely subtract 1 day
                $no_remaining_days--;
                if ($the_last_day_of_week == 6) {
                    // if the end date is a Saturday, then we subtract another day
                    $no_remaining_days--;
                }
            } else {
                // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
                // so we skip an entire weekend and subtract 2 days
                $no_remaining_days -= 2;
            }
        }

        //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
    //---->february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
        $workingDays = $no_full_weeks * 5;
        if ($no_remaining_days > 0) {
            $workingDays += $no_remaining_days;
        }

        //We subtract the holidays
        foreach ($holidays as $holiday) {
            $time_stamp=strtotime($holiday);
            //If the holiday doesn't fall in weekend
            if ($startDate <= $time_stamp && $time_stamp <= $endDate && date("N", $time_stamp) != 6 && date("N", $time_stamp) != 7) {
                $workingDays--;
            }
        }

        return $workingDays;
    }
    public function F_dateisholiday($date, $calendar)
    {
        //$days=$this->data->readconfig($calendar);
        $days=$GLOBALS[calendar];
        $w = date("N", strtotime($date));
        if ($w>5) {
            return 1;
        }
        $holidays=explode(",", $days);
        $md = date("d.m", strtotime($date));
        if (in_array($md, $holidays)) {
            return 1;
        }
            return 0;
    }
    public function F_dateaddworking($date, $days, $calendar)
    {
        $new_date=$date;
        $i=0;
        if ($days>=0) {
            while ($daysadded<$days) {
                $i++;
                $new_date=$this->F_dateadd($date, $i);
                $idholiday=$this->F_dateisholiday($new_date, $calendar);
                if ($idholiday==0) {
                    $daysadded++;
                }
            }
        } else {
            $days=-1*$days;
            while ($daysadded<$days) {
                $i--;
                $new_date=$this->F_dateadd($date, $i);
                $idholiday=$this->F_dateisholiday($new_date, $calendar);
                if ($idholiday==0) {
                    $daysadded++;
                }
            }
        }

        return $new_date;
    }
    public function F_dateadd($date = '', $days = 0, $workingonly = 0, $ignore_weekends = 0)
    {
        if ($workingonly>0) {
            $holidays=array('01.01.2013','02.01.2013');
            $tmpdate=$this->F_dateadd($date, $days);
            //echo"tmpdate:$tmpdate<br>";
            $workingdays=$this->getWorkingDays($date, $tmpdate, $holidays)-1;
            //echo"workingdays:$workingdays ($date,$tmpdate)<br>";
            $diffdays=$days-$workingdays;
            $days+=$diffdays;
        }
          $date=(mktime(0, 0, 0, substr($date, 3, 2), (substr($date, 0, 2)+$days), substr($date, 6, 4)));
        $date  = date('d.m.Y', $date);
        if($ignore_weekends>0){
            $day_of_week=$this->F_weekday($date);
            if($day_of_week==6)$date=$this->F_dateadd($date,-1);
            if($day_of_week==7)$date=$this->F_dateadd($date,+1);
        }
        return $date;
    }
    public function F_dateadd_year($date = '', $val = 1, $ignore_weekends = 0)
    {
        $date=(mktime(0, 0, 0, substr($date, 3, 2), (substr($date, 0, 2)), substr($date, 6, 4)));
        if ($val<0) {
            $sign='';
        } else {
            $sign='+';
        }
        $newdate = strtotime("$sign $val year", $date) ;
        $newdate = date('d.m.Y', $newdate);
        if($ignore_weekends>0){
            $day_of_week=$this->F_weekday($newdate);
            if($day_of_week==6)$newdate=$this->F_dateadd_day($newdate,-1);
            if($day_of_week==7)$newdate=$this->F_dateadd_day($newdate,+1);
        }
        return $newdate;
    }
    public function F_dateadd_month($date = '', $val = 1, $ignore_weekends = 0)
    {
        $date=(mktime(0, 0, 0, substr($date, 3, 2), (substr($date, 0, 2)), substr($date, 6, 4)));
        if ($val<0) {
            $sign='';
        } else {
            $sign='+';
        }
        $newdate = strtotime("$sign $val month", $date) ;
        $newdate = date('d.m.Y', $newdate);
        if($ignore_weekends>0){
            $day_of_week=$this->F_weekday($newdate);
            if($day_of_week==6)$newdate=$this->F_dateadd_day($newdate,-1);
            if($day_of_week==7)$newdate=$this->F_dateadd_day($newdate,+1);
        }
        return $newdate;
    }
    public function F_dateadd_week($date = '', $val = 1, $ignore_weekends = 0)
    {
        $date=(mktime(0, 0, 0, substr($date, 3, 2), (substr($date, 0, 2)), substr($date, 6, 4)));
        if ($val<0) {
            $sign='';
        } else {
            $sign='+';
        }
        $newdate = strtotime("$sign $val week", $date) ;
        $newdate = date('d.m.Y', $newdate);
        if($ignore_weekends>0){
            $day_of_week=$this->F_weekday($newdate);
            if($day_of_week==6)$newdate=$this->F_dateadd_day($newdate,-1);
            if($day_of_week==7)$newdate=$this->F_dateadd_day($newdate,+1);
        }
        return $newdate;
    }
    public function F_dateadd_day($date = '', $val = 1, $ignore_weekends = 0)
    {
        $date=(mktime(0, 0, 0, substr($date, 3, 2), (substr($date, 0, 2)), substr($date, 6, 4)));
        if ($val<0) {
            $sign='';
        } else {
            $sign='+';
        }
        $newdate = strtotime("$sign $val day", $date) ;
        $newdate = date('d.m.Y', $newdate);
        if($ignore_weekends>0){
            $day_of_week=$this->F_weekday($newdate);
            if($day_of_week==6)$newdate=$this->F_dateadd_day($newdate,-1);
            if($day_of_week==7)$newdate=$this->F_dateadd_day($newdate,+1);
        }
        return $newdate;
    }
    public function F_date_add($date = '', $val = 1, $what = 'day', $ignore_weekends = 0)
    {
        $date=(mktime(0, 0, 0, substr($date, 3, 2), (substr($date, 0, 2)), substr($date, 6, 4)));
        if ($val<0) {
            $sign='';
        } else {
            $sign='+';
        }
        $newdate = strtotime("$sign $val $what", $date) ;
        $newdate = date('d.m.Y', $newdate);
        return $newdate;
    }
    public function F_date_spilt($date = '31.12.2017 17:08:41.44235')
    {
        $d=substr($date, 0, 2);
        $m=substr($date, 3, 2);
        $y=substr($date, 6, 4);
        $hr=substr($date, 11, 2);
        $mn=substr($date, 14, 2);
        $sc=substr($date, 17, 2);
        $date="$d.$m.$y";
        $time="$hr:$mn:$sc";
        $datetime="$date $time";
        $usdate="$y-$m-$d";
        $usdatetime="$y-$m-$d $time";
        $res[datetime]=$datetime;
        $res[date]=$date;
        $res[time]=$time;
        $res[usdate]=$usdate;
        $res[usdatetime]=$usdatetime;
        $res[y]=$y;
        $res[m]=$m;
        $res[d]=$d;
        $res[hr]=$hr;
        $res[mn]=$mn;
        $res[sc]=$sc;
        return $res;
    }
    public function F_weekday($date)
    {
        $date_p=strtotime($date);
        $day = date('N', $date_p);
        //$day=$date_parts1[1];
        return $day;
    }
    public function F_weekdayname($date)
    {
        $date_p=strtotime($date);
        $day = date('D', $date_p);
        //$day=$date_parts1[1];
        return $day;
    }
    public function F_daysinmonth($date)
    {
        $date_p=strtotime($date);
        $day = date('t', $date_p);
        //$day=$date_parts1[1];
        return $day;
    }
    public function F_daysinyear($date)
    {
        $date_p=strtotime($date);
        $day = date('L', $date_p);
        $day=365+$day;
        return $day;
    }

    public function F_longdate($date)
    {
        $thedate=mktime(0, 0, 0, substr($date, 3, 2), substr($date, 0, 2), substr($date, 6, 4));
        $longdate=date("jS \d\a\y \of F, Y", $thedate);
        return $longdate;
    }

    public function F_JSdate($date)
    {
        $parts=explode(' ',$date);
        $days=explode('.',$parts[0]);
        $time=explode(':',$parts[1]);

        $thedate=mktime($time[0], $time[1], $time[2], $days[1], $days[0], $days[2]);
        $longdate=date("D M d Y H:i:s ", $thedate);
        return $longdate."GMT+0200 (EET)";
    }

    public function MT_date($date_mt)
    {
        if (!preg_match('/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})/', $date_mt, $match)) {
            echo "Wrong date format $date_mt";
        }
        //echo $this->pre_display($match,"match");
        if($match[2]=='24:00:00')$match[2]='23:59:59';
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', "$match[1] $match[2]");
        if(!$date){
            echo "Wrong date format $date_mt";
            return "";
        }
        //echo $this->html->pre_display($date,"date $date_mt");
        $result["date"]=$date->format('d.m.Y');
        $result["date2"]=$date->format('Y.m.d');
        $result["time"]=$date->format('H:i:s');
        $result["datetime"]=$date->format('d F Y H:i:s'). " CET";

        return $result;
    }

    public function timezone_PHP($tz){
        $array=[
            'UTC -11:00' => 'Pacific/Midway',
            'UTC -10:00' => 'US/Hawaii',
            'UTC -09:00' => 'US/Alaska',
            'UTC -08:00' => 'US/Pacific',
            'UTC -07:00' => 'US/Arizona',
            'UTC -06:00' => 'US/Central',
            'UTC -05:00' => 'US/Eastern',
            'UTC -04:30' => 'America/Caracas',
            'UTC -04:00' => 'Canada/Atlantic',
            'UTC -03:30' => 'Canada/Newfoundland',
            'UTC -03:00' => 'Greenland',
            'UTC -02:00' => 'Atlantic/Stanley',
            'UTC -01:00' => 'Atlantic/Azores',
            'UTC' => 'Europe/London',
            'UTC 00:00' =>  'Europe/London',
            'UTC -00:00' =>  'Europe/London',
            'UTC +00:00' =>  'Europe/London',
            'UTC +01:00' => 'Europe/Berlin',
            'UTC +02:00' => 'Europe/Athens',
            'UTC +03:00' => 'Europe/Moscow',
            'UTC +03:30' => 'Asia/Tehran',
            'UTC +04:00' => 'Europe/Volgograd',
            'UTC +04:30' => 'Asia/Kabul',
            'UTC +05:00' => 'Asia/Tashkent',
            'UTC +05:30' => 'Asia/Kolkata',
            'UTC +05:45' => 'Asia/Kathmandu',
            'UTC +06:00' => 'Asia/Yekaterinburg',
            'UTC +07:00' => 'Asia/Novosibirsk',
            'UTC +08:00' => 'Asia/Krasnoyarsk',
            'UTC +09:00' => 'Asia/Tokyo',
            'UTC +09:30' => 'Australia/Darwin',
            'UTC +10:00' => 'Asia/Yakutsk',
            'UTC +11:00' => 'Asia/Vladivostok',
            'UTC +12:00' => 'Asia/Magadan'
        ];
        $res=$array[$tz];
        return $res;
    }


}
