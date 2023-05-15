<?php
namespace Rozdol\Html;

use Rozdol\Utils\Utils;
use Rozdol\Dates\Dates;
use Rozdol\Utils\Utf8;

class Html
{
    private static $hInstance;

    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new Html();
        }
        return self::$hInstance;
    }

    public function __construct()
    {
            $this->dates = Dates::getInstance();
            $this->utils = Utils::getInstance();
            $this->utf8 = Utf8::getInstance();
    }

    function tablehead($what = '', $qry = '', $order = '', $addbutton = '', $fields = [], $sort = '', $tips = [], $class = 'table-notfull')
    {
        $last=count($fields)+1;
        foreach ($fields as $key => $field) {
            if (($this->utils->contains('date', strtolower($field)))||
               ($this->utils->contains('deadline', strtolower($field)))) {
                $jsheaders[]=($key+1).": {sorter:'rudates' }";
            }
            if (($this->utils->contains('amount', strtolower($field)))||
               ($this->utils->contains('balance', strtolower($field)))||
               ($this->utils->contains('paid', strtolower($field)))||
               ($this->utils->contains('discount', strtolower($field)))
                ) {
                    $jsheaders[]=($key+1).": {sorter:'ruamounts' }";
            }
        }
        if (count($jsheaders)>0) {
            $jsheaders=",".implode(',', $jsheaders);
        }

        if ($what!='') {
            $nosort_first_last=",headers: { 0: { sorter: false}, $last: {sorter: false} $jsheaders }";
        }
        if ($GLOBALS[force_table_full]>0) {
            $class="";
        }
        $id=$what;
        if ((($what=='')||($sort=='autosort'))&&($sort!='no_sort')) {
            $uniqid=uniqid();
            $id='JSsortableTable_'.$uniqid;
            $js='<script>$(document).ready(function() {$("#JSsortableTable_'.$uniqid.'").tablesorter({sortList: [[0,0], [1,0]]'.$nosort_first_last.'} );});</script>';
        }

        $search_icon="<a href='?act=search&what=$what' class='c'><i class='icon-search'></i></a>";
        $out.="$js
            <table class='fromtablehead table table-bordered table-striped-tr table-morecondensed tooltip-demo  $class' id='$id'>
            <thead  class='c'>
            <tr>
            ";
        if ($what!='') {
            $out.="<th>$search_icon</th>";
        }
        $i=0;
        foreach ($fields as $field) {
            //$field=strtolower($field);
            if ($tips[$i]!="") {
                $tip="data-original-title='$tips[$i]'";
            } else {
                $tip="";
            }
            $field=\util::l($field);
            if (($sort[$i]!="")&&($sort!='no_sort')&&(is_array($sort))) {
                $out.=" <th class='tooltip-test' $tip><a href='?$qry&sortby=$sort[$i]$order' TITLE='Sort'>".$field."</a></th>";
            } else {
                $out.=" <th class='tooltip-test' $tip>".$field."</th>";
            }
            $i++;
        }
        if (($what!='')&&($addbutton!='no_addbutton')) {
            $out.="<th style='text-align: center; width: 50px;'>$addbutton</th>";
        }
        if (($what=='')&&($addbutton!='')&&($addbutton!='no_addbutton')&&(!$this->utils->contains("icon-plus-sign", $addbutton))) {
            $out.="<th style='text-align: center; width: 50px;'><a href='$addbutton'><i class='icon-plus-sign'></i></a></th>";
        }
        $out.="</tr>
        </thead>
        <tbody>";
        return $out;
    }

    function tablefoot($i = 0, $totals = [], $totalrecs = 0, $opt = [])
    {
        $r=0;
        $hidelast=$opt['hidelast']*1;
        $continue=$opt['continue']*1;

        foreach ($totals as $total) {
            if ($total!="") {
                $y=$r;
            }
            $r++;
        }
        $out.="</tbody>";
        if (!$GLOBALS[force_table_nofooter]) {
            $out.="<tfoot>
             <tr>
                <th>$totalrecs</th>";
        }
        foreach ($totals as $total) {
            if ($j<=$y) {
                if (($j==1)&&($opt[title]!='')) {
                    $out.="<th>$opt[title]</th>";
                } elseif ($total!="") {
                    $out.="<th class='n'>".$this->money($total)."</th>";
                } else {
                    $out.="<th class='n'> </th>";
                }
            }
            $j++;
        }
        if ($hidelast!=1) {
            $out.="<th colspan='50'> </th>";
        }
                $out.="
             </tr>
            </tfoot>";

        if ($continue!=1) {
            $out.="      </table>";
        } else {
            $out.="<tbody>";
        }
        return $out;
    }
    public function tr_det($label, $value='', $class = '')
    {
        if (is_numeric($value))$class="$class n";
        return "<tr><td class='mt'><b>".\util::l($label).": </b></td><td class='m $class'>$value</td></tr>";
    }
    public function tr($text, $class = '')
    {
    //table row
        $out="<tr class='$class'>";
        if (is_array($text)) {
            $array=$text;
        } else {
            $array=explode('|', $text);
        }
        foreach ($array as $item) {
            if (is_numeric($item)) {
                $out.="<td class='n'>".$this->money($item)."</td>";
            } elseif($this->utils->contains('^', $item)) {
                $item=str_replace('^','',$item);
                $out.="<td class='n'>$item</td>";
            }else{
                $out.="<td>$item</td>";
            }
        }
        $out.="</tr>";
        return $out;
    }
    public function row($array, $valign = 'top')
    {
        $res.="<div class='row-fluid' style='margin-left:0px;'>
            <div class='span12'>
            <div style='overflow:auto;width:100%; overflow-x: hidden; overflow-y: hidden;'>
            <div style='width:1400px; vertical-align: top;'>";
        foreach ($array as $row) {
            $res.="<div class='' style='display:inline-block; vertical-align: $valign; margin-left:10px;'>$row</div>";
        }
            $res.="</div></div></div></div>";
        return $res;
    }

    function money($sum = 0, $curr = '', $opt = '', $dec = 2)
    {
        if (!is_numeric($sum)) {
            return $sum;
        }
        $zero='--';
        if ($sum < 0) {
            $div="neg";
        }
        if ($sum > 0) {
            $div="pos";
        }

        if ($this->utils->contains('no_thousands', $opt)) {
            $ts='';
        } else {
            $ts=' ';
        }
        if ($this->utils->contains('no_zeros', $opt)) {
            $zero='';
        }
        if ($this->utils->contains('force_zeros', $opt)) {
            $zero='0';
        }


        if ($sum == 0) {
            $div="zero";
            $sum=$zero;
            $curr="";
        } else {

            if ($this->utils->contains('accounting', $opt)) {
                if($sum>0){
                    $sum=number_format($sum, $dec, '.', $ts);
                    $sum="$sum ";
                }else{
                    $sum=abs($sum);
                    $sum=number_format($sum, $dec, '.', $ts);
                    $sum="($sum)";
                }

            }else{
                $sum=number_format($sum, $dec, '.', $ts);
            }
            if ($curr!='') {
                $sum.=" $curr";
            }
        }
        if ($this->utils->contains('warn_negative', $opt)&&($div=='neg')) {
            $sum=$this->tag($sum, 'span', 'badge badge-important');
        }
        if ($this->utils->contains('show_negative', $opt)&&($div=='neg')) {
            $sum=$this->tag($sum, 'span', 'text-error');
        }
        if ($this->utils->contains('show_positive', $opt)&&($div=='pos')) {
            $sum=$this->tag($sum, 'span', 'text-success');
        }
        if ($this->utils->contains('warn_positive', $opt)&&($div=='pos')) {
            $sum=$this->tag($sum, 'span', 'badge badge-success');
        }
        return $sum;
    }

    function tag($title = '', $type = 'h2', $class = '', $id = '')
    {
        //$title=\util::l($title);
        $result='';
        if($GLOBALS[offline_mode]){
            $GLOBALS[offline_messages][]=strip_tags("$type: $title");
            return strip_tags("$type: $title");;
        }else{
            if ($type=='foldered') {
                $result="<h3 class='foldered $class'><i class='' style='margin-right:26px;' data-original-title=''></i>$title</h3>";
            }
            if ($result=='') {
                $result="<$type class='$class' id='$id'>$title</$type>";
            }
            if ($result=='') {
                $result="<h2>$title</h2>";
            }
            return $result;
        }



    }

    function detalize($table, $id, $name='details' ,$chars = 0){
        if ($chars>0) {
            $chars=$chars*7;
            $name=$this->utf8->utf8_cutByPixel($name, $chars, false);
        }
        $link="<a href='?act=details&what=$table&id=$id'>$name</a>";
        return $link;

    }

    function shorter($text='' ,$chars = 400){
        return $this->utf8->utf8_cutByPixel($text, $chars, false);
    }


    function draw_progress_danger($val1 = 0, $val2 = 0, $thresh1 = 0, $thresh2 = 0)
    {
        if ($val1>100) {
            $val1=100;
        }

            $rest=100-$val1;
            $style='info';
        if ($val1<$thresh1) {
            $style='warning';
        }
            $graph= '<div class="progress"><div class="bar bar-'.$style.'" style="width: '.$val1.'%;"></div></div>';


        if ($val2>0) {
            if ($val2>100) {
                $val2>100;
            }
            $rest=100-$val2;
            $style='info';
            if ($val2<$thresh2) {
                $style='warning';
            }
            $graph.= '<div class="progress"><div class="bar bar-'.$style.'" style="width: '.$val2.'%;"></div></div>';
        }


        return $graph;
    }

    function draw_progress($val1 = 0, $val2 = 0)
    {
        if ($val1<=100) {
            $rest=100-$val1;
            $graph= '<div class="progress"><div class="bar bar-info" style="width: '.$val1.'%;"></div></div>';
        } else {
            $rest=round($val1-100);
            $rest2=100-$rest+1;
            $graph= '<div class="progress">
            <div class="bar bar-danger" style="width: '.$rest.'%;"></div>
            <div class="bar bar-warning" style="width: '.$rest2.'%;"></div>
            </div>';
        }
        if ($val2>0) {
            if ($val2<=100) {
                $rest=100-$val2;
                $graph.= '<div class="progress"><div class="bar bar-info" style="width: '.$val2.'%;"></div></div>';
            } else {
                $rest=round($val2-100);
                $rest2=100-$rest+1;
                $graph.= '<div class="progress">
            <div class="bar bar-danger" style="width: '.$rest.'%;"></div>
            <div class="bar bar-warning" style="width: '.$rest2.'%;"></div>
            </div>';
            }
        }

        return $graph;
    }

    function draw_progress_2($val0 = 0, $val1 = 0, $val2 = 0, $val3 = 0)
    {
        $val0=round($val0);
        $val1=round($val1);
        $val2=round($val2);
        $val3=round($val3);
        $graph='
        <div class="progress">
            <div class="bar bar-info" style="width: '.$val0.'%;"></div>
            <div class="bar bar-success" style="width: '.$val1.'%;"></div>
            <div class="bar bar-danger" style="width: '.$val2.'%;"></div>
            <div class="bar bar-warning" style="width: '.$val3.'%;"></div>
        </div>';

        return $graph;
    }

    function draw_progress_3($val0 = 0, $val1 = 0, $val2 = 0, $val3 = 0)
    {
        $val0=round($val0);
        $val1=round($val1);
        $val2=round($val2);
        $val3=round($val3);
        $graph='
        <div class="progress">
            <div class="bar bar-info" style="width: '.$val0.'%;"></div>
            <div class="bar bar-success" style="width: '.$val1.'%;"></div>
            <div class="bar bar-warning" style="width: '.$val2.'%;"></div>
            <div class="bar bar-danger" style="width: '.$val3.'%;"></div>
        </div>';

        return $graph;
    }
    public function filter($key = '', $value = '')
    {
        $filters = array(
            'email'=>FILTER_VALIDATE_EMAIL,
            'url'=>FILTER_VALIDATE_URL,
            'name'=>FILTER_SANITIZE_STRING,
            'id'=>FILTER_SANITIZE_NUMBER_INT,
            'pass'=>FILTER_SANITIZE_STRING,
            'address'=>FILTER_SANITIZE_STRING,
            'api_key'=>FILTER_SANITIZE_STRING,
            'user'=>FILTER_SANITIZE_STRING,
            'func'=>FILTER_SANITIZE_STRING,
            'param'=>FILTER_SANITIZE_STRING
        );
        // $options = array(
        //     'email'=>array(
        //         'flags'=>FILTER_NULL_ON_FAILURE
        //     ),
        //     'url'=>array(
        //         'flags'=>FILTER_NULL_ON_FAILURE
        //     ),
        //     'name'=>array(
        //         'flags'=>FILTER_NULL_ON_FAILURE
        //     ),
        //     'id'=>array(
        //         'flags'=>FILTER_NULL_ON_FAILURE
        //     ),
        //     'pass'=>array(
        //         'flags'=>FILTER_NULL_ON_FAILURE
        //     ),
        //     'address'=>array(
        //         'flags'=>FILTER_NULL_ON_FAILURE
        //     ),
        //     'api_key'=>array(
        //         'flags'=>FILTER_NULL_ON_FAILURE
        //     ),
        //     'user'=>array(
        //         'flags'=>FILTER_NULL_ON_FAILURE
        //     ),
        //     'func'=>array(
        //         'flags'=> FILTER_NULL_ON_FAILURE
        //     ),
        //     'param'=>array(
        //         'flags'=>FILTER_NULL_ON_FAILURE
        //     ),
        //     //... and so on
        // );
        if (!$filters[$key]) {
            $filters[$key]=FILTER_SANITIZE_STRING;
            if (substr($key, -3)=='_id') {
                $filters[$key]=FILTER_SANITIZE_NUMBER_INT;
            }
        }
        if (!$options[$key]) {
            $options=[
                $key=>[
                'flags'=>FILTER_NULL_ON_FAILURE
                ]
            ];
        }

        $filtered = filter_var($value, $filters[$key], $options[$key]);
        return $filtered;
    }
    public function readRQj($request = '')
    {
//json
        $JSONinput=$this->readRQs($request);


        $inputs = json_decode($JSONinput, true);
        $filtered = array();
        foreach ($inputs as $key => $value) {
            $filtered[$key] = $this->filter($key, $value);
            //$filtered[$key] = filter_var($value, $filters[$key], $options[$key]);
        }
        return $filtered;
    }
    public function readRQjd($request = '')
    {
//json dirty
        $JSONinput=$this->readRQs($request);
        $inputs = json_decode($JSONinput, true);
        return $inputs;
    }

    public function readValue($request = '')
    {
        //if (!$_GET[$request]) {$res=$_POST[$request];}else{$res=$_GET[$request];}
        if (!$_POST[$request]) {
            $res=$_GET[$request];
        } else {
            $res=$_POST[$request];
        }
        $res=str_replace("--", "- -", $res);
        return $res;
    }
    public function readRQc($request = '')
    {
//
        $val=$this->readValue($request);
        $val=stripslashes($val);
        $res=str_replace("\0", "", $val);
        return $res;
    }
    public function readRQcsv($request = '', $default = '', $is_numeric = 1, $as_list = 1)
    {
//parce csv
        $val=$this->readRQ($request);
        $val=str_ireplace("\n", ',', $val);
        $val=str_ireplace("\t", ',', $val);
        $val=str_ireplace(";", ',', $val);

        $array=explode(',', $val);
        $array=array_map('trim', $array);
        if ($is_numeric>0) {
            $array=array_filter($array, 'is_numeric');
        }
        if ($as_list>0) {
            $res=implode(',', $array);
        } else {
            $res=[];
            foreach ($array as $value) {
               if($value!='')$res[]=$value;
            }

        }
        return $res;
    }

    public function readRQ($request = '', $default = '')
    {
//strip tags
        $val=$this->readValue($request);
        $res=pg_escape_string(trim($val));
        $res=strip_tags($res);
        if (($res=='')&&($default!='')) {
            $res=$default;
        }
        return $res;
    }
    public function readRQn($request = '', $clean = 1, $default = 0)
    {
//numbers
        $val=$this->readRQ($request);
        if ($clean!=0) {
            $val=$this->utils->cleannumber($val, '.');
            // $val=str_replace(",",".",$val);
            // $val=str_ireplace(" ","",$val);
            // $val=str_ireplace("\t","",$val);
            // $val=str_ireplace(" ","",$val);
        }

        $res=(float)$val;
        if (($res==0)&&($default!=0)) {
            $res=$default*1;
        }
        return $res;
    }
    public function readRQh($request = '')
    {
//hypertext
        $val=$this->readValue($request);
        $res=htmlentities(trim($val, ENT_NOQUOTES));
        return $res;
    }
    public function readRQurl($request = '')
    {
//url
        $res=trim($this->readValue($request));
        $res=filter_var($res, FILTER_SANITIZE_URL);
        return $res;
    }
    public function readRQs($request = '')
    {
//string
        $val=$this->readValue($request);
        $res=pg_escape_string(trim($val));
        return $res;
    }
    public function readRQd($request = '', $check = '', $default = '')
    {
//date
        $val=$this->readValue($request);
        if (($val=='')&&($default!='')) {
            $val=$default;
        }
        $res=$this->dates->F_date($val, $check);
        return $res;
    }

    public function readRQdd($request = '', $days = 0)
    {
//date with added days
        $val=$this->readValue($request);
        if ($val=='') {
            $res=$this->dates->F_date($val, 1);
            $res=$this->dates->F_dateadd($res, $days);
        } else {
            $res=$this->dates->F_date($val);
        }
        return $res;
    }
    public function filename($val)
    {
        $res = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $val);
        // Remove any runs of periods (thanks falstro!)
        $res = mb_ereg_replace("([\.]{2,})", '', $res);
        return $res;
    }

    public function filepath($val)
    {
        $res = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\)\/.])", '', $val);
        // Remove any runs of periods (thanks falstro!)
        $res = mb_ereg_replace("([\.]{2,})", '', $res);
        return $res;
    }
    public function readRQf($request = '', $default = '')
    {
//filename
        $val=$this->readValue($request);
        if (($val=='')&&($default!='')) {
            $val=$default;
        }
        $res=$this->filename($val);
        // $res = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $val);
        // // Remove any runs of periods (thanks falstro!)
        // $res = mb_ereg_replace("([\.]{2,})", '', $res);
        return $res;
    }

        public function readRQp($request = '', $default = '')
        {
    //filename
            $val=$this->readValue($request);
            if (($val=='')&&($default!='')) {
                $val=$default;
            }
            $res=$this->filepath($val);
            return $res;
        }

    public function request_normalize()
    {
        $this->message('started normalize');
        //Rewrite route for back compatibility
        if (($this->readRQ('act')=='print')&&(substr($this->readRQ('what'), 0, 4)=='pdf_')) {
            $_GET[act]='pdf';
        }
        if (($this->readRQ('act')=='print')&&(substr($this->readRQ('what'), 0, 4)=='doc_')) {
            $_GET[act]='doc';
        }
        if (($this->readRQ('act')=='show')&&($this->readRQ('report')!='')) {
            $_GET[act]='report';
        }
        if ($this->readRQ('act')=='compare') {
            $_GET[act]='search';
            $_GET[what]=$_GET[what].'_compare';
        }
        //if($this->readRQ('act')=='compare'){$_GET[act]='search';}
        if ($this->readRQ('act')=='filter') {
            $_GET[act]='search';
        }
        if ($this->readRQ('act')=='form') {
            $_GET[act]='add';
        }
        if ($this->readRQ('what')=='a_translines') {
           // $_GET[what]='a_transactions';
           // $_POST[what]='a_transactions';
        }
        if ($this->readRQ('what')=='journal') {
            $_GET[what]='a_transactions';
            $_POST[what]='a_transactions';
        }

        // if(($_POST[act]!='')&&($_GET[act]==''))$_GET[act]=$_POST[act];
        // if(($_POST[what]!='')&&($_GET[what]==''))$_GET[what]=$_POST[what];

        // if(($_POST[act]=='')&&($_GET[act]!=''))$_POST[act]=$_GET[act];
        // if(($_POST[what]=='')&&($_GET[what]!=''))$_POST[what]=$_GET[what];

        $GLOBALS[act]=$this->readRQ('act');

        $GLOBALS[what]=$this->readRQ('what');
        $GLOBALS[plain]=$this->readRQn('plain');
        $GLOBALS[no_wrap]=$this->readRQn('no_wrap');
        if ($GLOBALS[no_wrap]>0) {
            $_POST[hide_menu]=1;
            $_POST[noexport]=1;
            $_POST[nocart]=1;
            $_POST[hide_footer]=1;
            $_POST[hide_title]=1;
            $_POST[notitle]=1;
        }

        if (in_array($GLOBALS[act], array('append','pdf','doc','graphdata','json','api'))) {
            $GLOBALS[plain]=1;
        }
        if (($GLOBALS[act]=='save')&&($GLOBALS[what]=='processdata')&&($this->readRQ('formaction')=='invoices:DOWNLOAD')) {
            $GLOBALS[plain]=1;
        }
        if (($GLOBALS[act]=='save')&&($GLOBALS[what]=='processdata')&&($this->readRQ('formaction')=='invoices:DOWNLOADX')) {
            $GLOBALS[plain]=1;
        }
        if (($GLOBALS[act]=='save')&&($GLOBALS[what]=='processdata')&&($this->readRQ('formaction')=='docs:DOWNLOADX')) {
            $GLOBALS[plain]=1;
        }
        if (($GLOBALS[act]=='save')&&($GLOBALS[what]=='processdata')&&($this->readRQ('formaction')=='docs:DOWNLOADZIP')) {
            $GLOBALS[plain]=1;
        }
        if (($GLOBALS[act]=='save')&&($GLOBALS[what]=='processdata')&&($this->readRQ('formaction')=='docs:DOWNLOADZIPSINGLE')) {
            $GLOBALS[plain]=1;
        }
        if (($GLOBALS[act]=='save')&&($GLOBALS[what]=='processdata')&&($this->readRQ('formaction')=='all:EXPORTZIP')) {
            $GLOBALS[plain]=1;
        }
        if (($GLOBALS[act]=='save')&&($GLOBALS[what]=='shoppingcart')) {
            $GLOBALS[plain]=1;
        }

        if (($GLOBALS[act]=='details')&&($GLOBALS[what]=='uploads')) {
            $GLOBALS[plain]=1;
        }
        if ($this->readRQ('nowrap')!='') {
            $GLOBALS[plain]=1;
        }

        if ((in_array($GLOBALS[act], array('show','details','report','r','v','d')))&&($GLOBALS[plain]=='')&&(!in_array($GLOBALS[what], array('groupaccess')))) {
            // $GLOBALS[return_to_act]=$GLOBALS[act];
            // $GLOBALS[return_to]=$GLOBALS[what];
            // $GLOBALS[return_to_id]=$this->readRQ('id');
            $this->set_reflink();
        }
        if ((in_array($GLOBALS[act], array('save')))&&($GLOBALS[plain]=='')&&(!in_array($GLOBALS[what], array('groupaccess'))&&($this->readRQ('back_to_url')!=''))) {
            $this->set_reflink($this->readRQ('back_to_url'));
        }

        $GLOBALS[raw_data]=$GLOBALS[plain]>0;
        if ($GLOBALS[what]=='') {
            $GLOBALS[what]=$this->readRQ('table');
        }

        $GLOBALS[request]=array_unique(array_merge($_GET, $_POST, $_REQUEST));
        $GLOBALS[request_txt]=strip_tags(json_encode($GLOBALS[request]));
        if (strlen($GLOBALS[request_txt])>500) {
            $GLOBALS[request_txt]="Huge data";
        }
        //ksort($GLOBALS[request]);

        $GLOBALS[cart] = $_SESSION['cart'];

        if ($GLOBALS[act]=='d') {
            $GLOBALS[act]='details';
            $_POST[act]=$GLOBALS[act];
            $_GET[act]=$GLOBALS[act];
        }
        if ($GLOBALS[act]=='v') {
            $GLOBALS[act]='show';
            $_POST[act]=$GLOBALS[act];
            $_GET[act]=$GLOBALS[act];
        }
        if ($GLOBALS[act]=='r') {
            $GLOBALS[act]='report';
            $_POST[act]=$GLOBALS[act];
            $_GET[act]=$GLOBALS[act];
        }
        if ($GLOBALS[act]=='s') {
            $GLOBALS[act]='save';
            $_POST[act]=$GLOBALS[act];
            $_GET[act]=$GLOBALS[act];
        }
        if ($GLOBALS[act]=='a') {
            $GLOBALS[act]='api';
            $_POST[act]=$GLOBALS[act];
            $_GET[act]=$GLOBALS[act];
        }


        if ($GLOBALS[what]=='d') {
            $GLOBALS[what]='documents';
            $_POST[what]=$GLOBALS[what];
            $_GET[what]=$GLOBALS[what];
        }
        if ($GLOBALS[what]=='i') {
            $GLOBALS[what]='invoices';
            $_POST[what]=$GLOBALS[what];
            $_GET[what]=$GLOBALS[what];
        }
        if ($GLOBALS[what]=='p') {
            $GLOBALS[what]='partners';
            $_POST[what]=$GLOBALS[what];
            $_GET[what]=$GLOBALS[what];
        }
        if ($GLOBALS[what]=='c') {
            $GLOBALS[what]='consent';
            $_POST[what]=$GLOBALS[what];
            $_GET[what]=$GLOBALS[what];
        }

        //$this->message('ended normalize');

        return true;
    }
    function HT_Error($msg = '')
    {
        echo $this->error($msg);
    }
    function error($msg = '')
    {
        $msg=trim($msg);
        if($msg!=''){
            $this->utils->log("ERROR: $msg");
            if($GLOBALS[offline_mode]){
                $this->message($msg, 'ERROR');
            }else{
                $message=$this->message($msg, 'ERROR', 'alert-error');
                echo $message;
                echo '<script>$("#livestatus").html("'.$message.'");</script>'."\n";
                ob_flush();
                flush();
                exit;
            }
        }
    }
    function warn($msg = '')
    {
        if($GLOBALS[offline_mode]){
            $GLOBALS[offline_messages][]=strip_tags("WARN: $msg");
        }else{
            echo $this->message($msg, '', 'alert-error');
        }

    }

    function message($msg = '', $title = '', $class = 'alert-info')
    {
        if($GLOBALS[offline_mode]){
            if($title=='')$title=$class;
            $GLOBALS[offline_messages][]=strip_tags("$title: $msg");
        }else{
            if ($this->utils->contains('alert-', $class)>0) {
                $class="alert $class";
            }
            if ($title!='') {
                $header="<h2>$title</h2>";
            }
            return "<div class='$class'>$header$msg</div>";
        }
    }

    function shout($html = '')
    {
        if($GLOBALS[offline_mode]){
            $GLOBALS[offline_messages][]=strip_tags($html);
        }else{
            echo $html;
            ob_flush();
            flush();
        }
        return true;
    }
    function dd($in=[],$exit=0){
        if($GLOBALS[offline_mode]){
            $out=json_encode(['debug'=>$in]);
        }else{
            $out=$this->pre_display($in,'Debug');
        }
        echo $out;
        if($exit)exit;
    }
    function card($data=['title'=>'Orders Received', 'icon'=>'cart-plus', 'value'=>486,'title2'=>'Completed Orders','value2'=>351, 'color'=>'blue']){
        $card='<div class="span3">
            <div class="card bg-c-'.$data[color].' order-card">
                <div class="card-block">
                    <h4 class="">'.$data[title].'</h4>
                    <h2 class="text-right  text-end"><i class="fa fa-'.$data[icon].' f-left"></i><span>'.$data[value].'</span></h2>
                    <p class="">'.$data[title2].'<span class="f-right">'.$data[value2].'</span></p>
                </div>
            </div>
        </div>';
        return $card;
    }
    function pre_display($text = '', $title = '', $class = '', $code = 0)
    {
        if ($_REQUEST[act]=='api') {
            if ($title=='') {
                $title='output';
            }
            $out=json_encode(["$title"=>$text]);
        } else {
            if ($title!='') {
                $out.=$this->tag($title, 'foldered');
            }
            $out.="<pre class='$class'>";
            if ($code==0) {
                 $out.=htmlspecialchars(print_r($text, true));
            } else {
                $out.=htmlspecialchars(var_export($text, true));
            }
            $out.= "</pre>";
        }
        return $out;
    }

    function area_display($text = '', $title = '')
    {
        if ($title!='') {
            $out.= "<h3>$title</h3>";
        }
        $out.= "<textarea class='span12' rows=10>";
        $out.=print_r($text, true);
        $out.= "</textarea>";
        return $out;
    }
    function rq_display()
    {
        $out.=$this->pre_display($GLOBALS, '$GLOBALS');
        //$out.=$this->pre_display($_POST,'$_POST');
        return $out;
    }
    function set_reflink($address = '')
    {
        //if($address==''){$reflink='?'.$_SERVER[QUERY_STRING];}else{$reflink=$address;}//orgqry
        if ($address=='') {
            $reflink='?'.$GLOBALS[orgqry];
        } else {
            $reflink=$address;
        }//orgqry
        if (strlen($reflink)>500) {
            $reflink='';
        }
        session_start(); // set reflink
        setcookie("reflink", $reflink, time()+86400);
        $_SESSION['reflink'] = $reflink;
        session_write_close();
        ob_flush();
        flush();
    }
    function help($html = '')
    {
        $text="<img src='".ASSETS_URI."/assets/img/custom/help.png' height=12 width=12 onMouseover=\"showhint('$html', this, event, '400px');\">";
        return $text;
    }
    function help_link($link = '')
    {
        $text="<a href='$link' target='_blank'><img src='".ASSETS_URI."/assets/img/custom/help.png' height=12 width=12></a>";
        return $text;
    }

    public function putHeader($title = '', $css = array(), $scripts = array())
    {
        header('X-Accel-Buffering: no');
        // header('Content-Encoding: none;');
        global $act, $what, $owner;
        $bs=$GLOBALS['bootstrap_ver'];
        $jq=$GLOBALS['jquery_ver'];
        if ($bs=="2.2.1") {
            $bootstrap='
            <link href="'.ASSETS_URI.'/assets/bootstrap/'.$bs.'/bootstrap.css" rel="stylesheet">
            <link href="'.ASSETS_URI.'/assets/bootstrap/'.$bs.'/bootstrap-responsive.css" rel="stylesheet">
            <link href="'.ASSETS_URI.'/assets/css/glyphicons-h.css" rel="stylesheet">
            <link href="'.ASSETS_URI.'/assets/css/glyphicons.css" rel="stylesheet">
            <link href="'.ASSETS_URI.'/assets/css/FCcheckbox.css" rel="stylesheet">
            <link href="'.ASSETS_URI.'/assets/fa5/css/all.css" rel="stylesheet">
            ';
            $bootstrap_ej='
           <link href="'.ASSETS_URI.'assets/EJ/Content/ejthemes/ej.widgets.core.min.css" rel="stylesheet" />
           <link href="'.ASSETS_URI.'assets/EJ/Content/ejthemes/bootstrap-theme/ej.web.all.min.css" rel="stylesheet" />
           <link href="'.ASSETS_URI.'assets/EJ/Scripts/CodeMirror/codemirror.min.css" rel="stylesheet" />
           <link href="'.ASSETS_URI.'assets/EJ/Content/default.css" rel="stylesheet"/>

            ';
            $bootstrap_js='
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-transition.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-alert.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-modal.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-dropdown.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-scrollspy.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-tab.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-tooltip.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-popover.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-button.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-collapse.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-carousel.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-typeahead.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/bootstrap-datepicker.js"></script>
            ';

            $bootstrap_js_ej='
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/jquery-3.4.1.min.js" type="text/javascript"> </script>
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/default.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/jsrender.min.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/ej.web.all.min.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/CodeMirror/codemirror.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/CodeMirror/javascript.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/CodeMirror/css.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/CodeMirror/xml.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/CodeMirror/htmlmixed.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'assets/EJ/Scripts/CodeMirror/clike.js" type="text/javascript"></script>
            ';
        } else {
            $bootstrap ='<link href="'.ASSETS_URI.'/assets/bootstrap/'.$bs.'/css/bootstrap.css" rel="stylesheet">';
            //$bootstrap.='<link href="'.ASSETS_URI.'/assets/bootstrap/'.$bs.'/css/bootstrap-grid.css" rel="stylesheet">';
            //$bootstrap.='<link href="'.ASSETS_URI.'/assets/bootstrap/'.$bs.'/css/bootstrap-reboot.css" rel="stylesheet">';

            //$bootstrap_js ='<script src="'.ASSETS_URI.'/assets/js/popper.js"></script>';
            //$bootstrap_js.='<script src="'.ASSETS_URI.'/assets/js/tooltip.js"></script>';
            $bootstrap_js.='<script src="'.ASSETS_URI.'/assets/js/tether.js"></script>';
            $bootstrap_js.='<script src="'.ASSETS_URI.'/assets/bootstrap/'.$bs.'/js/bootstrap.js"></script>';
            ///$bootstrap_js.='<script src="'.ASSETS_URI.'/assets/bootstrap/'.$bs.'/js/bootstrap-bundle.js"></script>';
        }
        if ($title) {
            $content['header']['title']=$title;
        } else {
            $content['header']['title']=DB_NAME." - $what $act";
        }
        if ($css) {
            foreach ($css as $name => $val) {
                //echo "$name => $val <br>";
                if ($val=='fw') {
                    $content['header']['links'].='<link href="'.ASSETS_URI.'/assets/css/'.$name.'" rel="stylesheet">'."\n";
                }
                if ($val=='theme') {
                    $content['header']['links'].='<link href="'.ASSETS_URI.'/themes/'.$GLOBALS['settings']['theme'].'/css/'.$name.'" rel="stylesheet">'."\n";
                }
                if ($val=='link') {
                    $content['header']['links'].='<link '.$name.' >'."\n";
                }
            }
        } else {
            $ico_file=ROOT_DIR."/public/assets/ico/".APP_NAME."/favicon.ico";
            $ico_file=(file_exists($ico_file))?APP_URI."/assets/ico/".APP_NAME."/favicon.ico":ASSETS_URI."/assets/ico/favicon.ico";

            if (file_exists(ROOT_DIR.'www/assets/css/fw.css')) {
                $fw_css=APP_URI.'/fw.css';
            } else {
                $fw_css=ASSETS_URI.'/assets/css/fw.css';
            }

            $content['header']['links']='
                '.$bootstrap.'

                <link href="'.$ico_file.'" rel="shortcut icon favicon" type="image/x-icon">
                <link href="'.ASSETS_URI.'/assets/ico/apple-touch-icon-114-precomposed.png"  rel="apple-touch-icon-precomposed" sizes="114x114">
                <link href="'.ASSETS_URI.'/assets/ico/apple-touch-icon-72-precomposed.png" rel="apple-touch-icon-precomposed" sizes="72x72" >
                <link href="'.ASSETS_URI.'/assets/ico/apple-touch-icon-57-precomposed.png" rel="apple-touch-icon-precomposed" >

                <link href="'.ASSETS_URI.'/assets/css/dropzone.css" rel="stylesheet">

                <!-- <link href="'.ASSETS_URI.'/assets/css/dropdown/themes/default/helper_deleted.css" media="screen" rel="stylesheet" type="text/css" /> -->

                <link href="'.ASSETS_URI.'/assets/css/dropdown/dropdown.css" media="screen" rel="stylesheet" type="text/css" />
                <link href="'.ASSETS_URI.'/assets/css/datepicker.css" media="screen" rel="stylesheet" type="text/css" />
                <link href="'.ASSETS_URI.'/assets/css/toastr.min.css" media="screen" rel="stylesheet" type="text/css" />
                <link href="'.ASSETS_URI.'/assets/css/dropdown/themes/black/default.ultimate.css" media="screen" rel="stylesheet" type="text/css" />
                <link href="'.ASSETS_URI.'/assets/css/ffupload/css/style.css" rel="stylesheet" >
                <link href="'.ASSETS_URI.'/assets/css/ffupload/css/jquery.fileupload-ui.css" rel="stylesheet" >
                <link href="'.$fw_css.'" rel="stylesheet" type="text/css" />

            ';
        }
        if ($scripts) {
            foreach ($scripts as $name => $val) {
                //echo "$name => $val <br>";
                if ($val=='fw') {
                    $content['header']['scripts'].='<script src="'.ASSETS_URI.'/assets/js/'.$name.'" type="text/javascript"></script>'."\n";
                }
                if ($val=='theme') {
                    $content['header']['scripts'].='<script src="'.ASSETS_URI.'/themes/'.$GLOBALS['settings']['theme'].'/js/'.$name.'" type="text/javascript"></script>'."\n";
                }
                if ($val=='link') {
                    $content['header']['scripts'].='<script src="'.$name.'" type="text/javascript"></script>'."\n";
                }
            }
        } else {
            if($GLOBALS[gid]>1){
            $toastr_options="
                        'closeButton': true,
                        'debug': false,
                        'newestOnTop': true,
                        'progressBar': true,
                        'positionClass': 'toast-top-right',
                        'preventDuplicates': false,
                        'onclick': null,
                        'showDuration': '1000',
                        'hideDuration': '1000',
                        'timeOut': '10000',
                        'extendedTimeOut': '1000',
                        'showEasing': 'swing',
                        'hideEasing': 'linear',
                        'showMethod': 'fadeIn',
                        'hideMethod': 'fadeOut'
                        ";
            $pusher_js="<script>
            // Enable pusher logging - don't include this in production
                // Pusher.logToConsole = true;

                var pusher = new Pusher('".getenv(PUSHER_KEY)."', {
                    cluster: 'eu'
                });

                var channel = pusher.subscribe('".$GLOBALS[domain]."-channel-all');
                channel.bind('".$GLOBALS[domain]."-event', function(data) {
                    // alert(JSON.stringify(data));
                    toastr.info(data['message'],data['title'],{ $toastr_options });
                });

                var channel = pusher.subscribe('".$GLOBALS[domain]."-channel-gid-".$GLOBALS[gid]."');
                channel.bind('".$GLOBALS[domain]."-event', function(data) {
                   // alert(JSON.stringify(data));
                    toastr.info(data['message'],data['title'],{ $toastr_options });
                });

                var channel = pusher.subscribe('".$GLOBALS[domain]."-channel-uid-".$GLOBALS[uid]."');
                channel.bind('".$GLOBALS[domain]."-event', function(data) {
                   // alert(JSON.stringify(data));
                    toastr.info(data['message'],data['title'],{ $toastr_options });
                });

                </script>";
            }
            $content['header']['scripts']='
            <script src="'.ASSETS_URI.'/assets/js/jquery-'.$jq.'.min.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'/assets/js/jquery.ajaxq-0.0.1.js" type="text/javascript"></script>
            <script src="'.ASSETS_URI.'/assets/js/jquery.validate.js" type="text/javascript"></script>
            '.$bootstrap_js.'
            <script src="'.ASSETS_URI.'/assets/js/pusher.min.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/toastr.min.js"></script>
            '.$pusher_js.'
            <script src="'.ASSETS_URI.'/assets/js/jquery.dropdown.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/jquery.jeditable.mini.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/jquery.tablesorter.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/app/confirm.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/app/ajax.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/app/hint.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/app/basket.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/app/select_items.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/app/subfolders.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/app/calendar1.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/app/funcs.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/application.js"></script>
            <script src="'.ASSETS_URI.'/assets/js/ffupload/js/jquery.ui.widget.js"></script>

            <script src="'.ASSETS_URI.'/assets/fusioncharts/js/fusioncharts.js"></script>
            <script src="'.ASSETS_URI.'/assets/fusioncharts/js/themes/fusioncharts.theme.fint.js"></script>



            ';
        }




        if ($GLOBALS['js']['header']) {
            foreach ($GLOBALS['js']['header'] as $name => $val) {
                $content['header']['scripts'].=$val.\n;
            }
        }

        require(FW_DIR.DS.'helpers'.DS.'head.php');
        echo $html;
    }

    public function putFooter($scripts = [])
    {
        $GLOBALS['endtime']=microtime(true);
        $runtime=round($GLOBALS['endtime']-$GLOBALS['starttime'], 2);
        if ($scripts) {
            foreach ($scripts as $name => $val) {
                //echo "$name => $val <br>";
                if ($val=='fw') {
                    $content['scripts'].='<script src="'.ASSETS_URI.'/assets/js/'.$name.'" type="text/javascript"></script>'."\n";
                }
                if ($val=='theme') {
                    $content['scripts'].='<script src="'.ASSETS_URI.'/themes/'.$GLOBALS['settings']['theme'].'/js/'.$name.'" type="text/javascript"></script>'."\n";
                }
                if ($val=='link') {
                    $content['scripts'].='<script src="'.$name.'" type="text/javascript"></script>'."\n";
                }
            }
            //echo '<script src="'.ASSETS_URI.'/assets/js/app_final.js" type="text/javascript"></script>';
            echo $content['scripts'];
            echo "\n\t</body>\n</html>";
        } else {
            if (!$GLOBALS[settings][hide_footer_info]) {
                $git_file = APP_DIR.DS.'.git';
                if (file_exists($git_file)) {
                    $tz = 'Europe/Nicosia';
                    date_default_timezone_set($tz);
                    $modified= " - ". date("Y.m.d H:i:s", filemtime($git_file));
                }
                $hostname = gethostname();
                $content['footer']="<a href='#top'>⟰</a> | Ver.: <font color='#aa0000'><b>$GLOBALS[app_version]</b></font> $modified | prj:$GLOBALS[project] | app:".APP_NAME." | db:".$GLOBALS['DB']['DB_NAME']." | dm:".$GLOBALS['DB']['DB_DOMAIN']." | cid:".CLIENT_ID." | Runtime: $runtime | Mem:".(memory_get_peak_usage(1)/(1024*1024))." Mb | PID:$GLOBALS[project] | IP:".$GLOBALS[_SERVER][SERVER_ADDR]." | UGID: $GLOBALS[uid]@$GLOBALS[gid] | H:$hostname | $GLOBALS[status] | <b>$GLOBALS[copyright]</b>";
            }
            //$content['footer'].= $this->pre_display($GLOBALS,"result");
            //unset($content['footer']);
            if ($GLOBALS['access']['view_debug']) {
                $content['info']['debug']=$this->collapse($this->rq_display(), 'Debug info', false);
            }

            if ($GLOBALS[fileupload]==1) {
                $content['scripts']='
                    <!-- The Templates plugin is included to render the upload/download listings -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/tmpl.min.js"></script>
                    <!-- The Load Image plugin is included for the preview images and image resizing functionality -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/load-image.min.js"></script>
                    <!-- The Canvas to Blob plugin is included for image resizing functionality -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/canvas-to-blob.min.js"></script>
                    <!-- Bootstrap JS and Bootstrap Image Gallery are not required, but included for the demo -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/bootstrap.min.js"></script>
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/bootstrap-image-gallery.min.js"></script>
                    <!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/jquery.iframe-transport.js"></script>
                    <!-- The basic File Upload plugin -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/jquery.fileupload.js"></script>
                    <!-- The File Upload image processing plugin -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/jquery.fileupload-ip.js"></script>
                    <!-- The File Upload user interface plugin -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/jquery.fileupload-ui.js"></script>
                    <!-- The localization script -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/locale.js"></script>
                    <!-- The main application script -->
                    <script src="'.ASSETS_URI.'/assets/js/ffupload/js/main.js"></script>

                    <!-- The XDomainRequest Transport is included for cross-domain file deletion for IE8+ -->
                    <!--[if gte IE 8]><script src="'.ASSETS_URI.'/assets/js/ffupload/js/cors/jquery.xdr-transport.js"></script><![endif]-->
            ';
            }



            if ($GLOBALS['js']['footer']) {
                foreach ($GLOBALS['js']['footer'] as $name => $val) {
                    $content['scripts'].=$val."\n";
                }
            }



            require(FW_DIR.DS.'helpers'.DS.'footer.php');
            echo $html;
        }
    }
    public function noPrint($html = '')
    {
        return "<span media='print'  class='noPrint'>$html</span>";
    }
    public function putTestBS()
    {
        $what=$this->readRQ('what');
        if ($what=='bs4') {
            require(FW_DIR.'helpers'.DS.'bs4.html');
        } else {
            require(FW_DIR.'helpers'.DS.'bs2.html');
        }

        //echo $html;
    }

    function tick($value = '')
    {
        //if(($value==1)||($value=='t')) return "<img src='".ASSETS_URI."/assets/img/custom/ok.gif'>"; else return "<img src='".ASSETS_URI."/assets/img/custom/cancel.gif'>";
        if (($value==1)||($value=='t')) {
            return "<span class='btn btn-micro btn-success'><i class='icon-ok icon-white'></i></span>";
        } else {
            return "<span class='btn btn-micro btn-danger'><i class='icon-remove icon-white'></i></span>";
        }
    }

    public function putWrap_in($content = array())
    {
        if ($content['container']['fluid']!='') {
            $class='-fluid';
        }
        $class='-fluid';
        echo '
        <div class="main-screen container'.$class.'">
            <div class="row'.$class.'">
                <div class="span12">
                    <span id="livestatus"></span>
                    <span id="childs_"> </span>';
    }

    public function putWrap_out($content = array())
    {
        echo '</div></div></div>';
    }

    public function putLogin($content = array())
    {
        $hostname = gethostname();
        $info="ip:".$GLOBALS[ip]." | db:".$GLOBALS['DB']['DB_NAME']." | dm:".$GLOBALS['DB']['DB_DOMAIN']." | app:".APP_NAME. " | H:$hostname";

        if ((!($this->utils->is_IP_local($_SERVER['REMOTE_ADDR'])))&&((getenv('MFA_AUTH')||($GLOBALS[settings][use_mfa])))) {
        //if(1==1){
            $content['html'].='<label> </label>
            <div class="input-prepend">
                <span class="add-on"><i class="icon-time"></i></span>
            <input type="text" class="form-control" placeholder="OTP if installed" name="otp" id="otp">
                </div>
            <br>';
        }


        $content_html=$content['html'];
        require(FW_DIR.DS.'helpers'.DS.'login.php');
        if ($content['options']['noecho']) {
            return $html;
        } else {
            echo $html;
        }
    }

    //====
    function tabs_ajax($items = [], $placeholder = '')
    {
        if ($placeholder=='') {
            $placeholder='tab_page_'.uniqid();
            $placeholder_div="<div id='$placeholder'></div>";
        }

        foreach ($items as $name => $link) {
            $list.="<li><a href='#' data-toggle='tab' onclick='ajaxFunction(\"$placeholder\", \"$link\");'onmouseover=\"this.style.cursor='pointer';\">$name</a></li>\n";
        }

        $out.="
        <ul class='nav nav-tabs'>
          $list
        </ul>
        $placeholder_div
        ";
        return $out;
    }

    function tabs($items = [], $active = '')
    {
        if ($placeholder=='') {
            $placeholder='tab_page_'.uniqid();
            $placeholder_div="<div id='$placeholder'></div>";
        }

        foreach ($items as $name => $link) {
            $class='';

            if ($name==$active) {
                $class="active";
            }
            $list.="<li class='$class'><a href='$link'>$name</a></li>\n";
        }

        $out.="
        <ul class='nav nav-tabs'>
          $list
        </ul>
        $placeholder_div
        ";
        return $out;
    }



    function form_start($what = '', $id = 0, $title = '', $opt = [])
    {
        if ($title=='') {
            //$title=ucwords(str_ireplace('_',' ', $what));
            $title=\util::l(strtolower(str_ireplace('_',' ', $what)));
        }
        if ($title=='no') {
            $title='';
        }
        if ($id==0) {
            $action=\util::l('add');
        } else {
            $action=\util::l('edit');
            $id_var="id:$id";
        }
        //$class='form-horizontal';
        $save_url="?csrf=$GLOBALS[csrf]&act=save&what=$what";
        if ($opt['url']) {
            $save_url=$opt['url'];
        }
        if ($opt['class']) {
            $class=$opt['class'];
        }
        if ($opt['well_class']) {
            $well_class=$opt['well_class'];
        }
        if ($title=='') {
            $action='';
        }

        if ($opt['title']!='') {
            $action="";
            $title=\util::l($opt['title']);
        }
        if ($opt['validation']) {
            $validation=$this->validation($what, $opt['validation']);
            //echo $this->pre_display($validation,"validation");
        }
        $header="<h1>$action $title</h1>
        <p>$id_var</p>
        </br>";
        if ($title=='no_title') {
            $header='';
        }

        //$res="<div class='row-fluid'>";
        $res.="$validation<div class='well $well_class'>
        $header
        <form method='POST' action='$save_url' class='$class' id='form_$what' name='form_$what'>\n";

        $GLOBALS['js']['form'].="$(document).ready(function () {
            $('#form_$what').validate({
                rules: {\n";
        return $res;
    }
    function form_submit($text = 'Submit', $value = 'save', $what = '')
    {
        $GLOBALS[tabindex]++;
        $btn_id="btn_save_$what";
        $text=\util::l($text);
        $res="<button type='submit' class='btn btn-primary' name='act' value='$value'  tabindex='$GLOBALS[tabindex]'>$text</button>";
        //<input type='hidden' name='save' value='$value'>

        $res="<div class='form-actions'>
        <button type='submit' class='btn btn-primary' id='$btn_id' tabindex='$GLOBALS[tabindex]'>$text</button>";
        $GLOBALS[tabindex]++;
        if ($GLOBALS[cancel_button]) {
            $res.=" <button type='reset' class='btn' onClick='$GLOBALS[cancel_button]'  tabindex='$GLOBALS[tabindex]'>".\util::l('Cancel')."</button>
    </div>";
        } else {
            $res.=" <button type='reset' class='btn' onClick='history.go(-1);'  tabindex='$GLOBALS[tabindex]'>".\util::l('Cancel')."</button>
    </div>";
        }
        $GLOBALS['js']['form'].="errorPlacement: function(error, element) {
                error.addClass('alert alert-error');
                error.insertBefore('#btn_save_".$what."').delay(3000).fadeOut();
                alert('".$messages."');
            },
            highlight: function(element, errorClass) {
              $(element).addClass('invalid');
              $(element).style.color = \"red\";

            },
            wrapper: 'div',
                }
            })
        });";
        return $res;
    }

    function validation($what = '', $validation = [])
    {
        foreach ($validation as $key => $value) {
            //echo "$key => $value<br>";
            $rules[]="$key: {required: true}";
            //$messages[]="$key: $value";
        }
        $rules=implode(',', $rules);
        $messages=implode(',', $messages);
        $js="
        $('#form_$what').validate({
            rules: {
                $rules
            },
            messages: {
                $messages
            },
            errorPlacement: function(error, element) {
                error.addClass('alert alert-error');
                error.insertBefore('#btn_save_".$what."').delay(3000).fadeOut();
                console('".$messages."');
            },
            highlight: function(element, errorClass) {
              $(element).addClass('invalid');
              //$(element).style.color = \"red\";

            },
            wrapper: 'div',
        });

        ";

        $js="$(document).ready(function(){".$js."});";
        $js="<script>$js</script>";
        //$js="<pre>$js</pre>";
        return $js;
    }
////$(element).setAttribute('style','background-color: #f2dede; border-color: #b94a48;');
    function form_end()
    {



//  $GLOBALS['js']['form'].="},
//   highlight: function (element) {
//       $(element).closest('.control-group').removeClass('success').addClass('error');
//   },
//   success: function (element) {
//       element.text('OK!').addClass('valid')
//           .closest('.control-group').removeClass('error').addClass('success');
//   }
// });
// });\n";

        $res="  </form>
</div>\n";

        $res.="<script>".$GLOBALS['js']['form']."</script>";
        return $res;
    }

    function form_hidden($name = 'reflink', $value = '/')
    {
        $res="<input type='hidden' name='$name' id='$name' value='$value'>\n";
        return $res;
    }
    public function form_confirmations()
    {
        $GLOBALS[tabindex]++;
        $noduplicate=$this->readRQn('noduplicate');
        $id=$this->readRQn('id');
        if ($this->readRQn('backtoedit')>0) {
            $backtoedit_sel='checked';
        }
        if ($noduplicate>0) {
            $backtoedit_sel='';
        }
        if ($this->readRQn('backtodetails')>0) {
            $backtodetails_sel='checked';
        }
        if ($GLOBALS[backtodetails]>0) {
            $backtodetails_sel='checked';
        }
        $backtoedit_btn="<label><input type='checkbox' name='backtoedit' value='1' $backtoedit_sel tabindex='$GLOBALS[tabindex]'/> ".\util::l('Edit this record after save')."</label>";
        $GLOBALS[tabindex]++;
        $backtodetails_btn="<label><input type='checkbox' name='backtodetails' value='1' $backtodetails_sel tabindex='$GLOBALS[tabindex]'/> ".\util::l('Show details of the record after save')."</label>";
        $GLOBALS[tabindex]++;
        if (($id>0)&&($noduplicate==0)) {
            if ($this->readRQ('duplicate')>0) {
                $duplicate_sel='checked';
            }
            $duplicate_btn="<label><input type='checkbox' name='duplicate' value='1' $duplicate_sel tabindex='$GLOBALS[tabindex]'/> ".\util::l('Duplicate the record')."</label>";
        }
        $res="<hr>$backtodetails_btn $backtoedit_btn $duplicate_btn";
        return $res;
    }

    function form_text($name = '', $value = '', $label = '', $placeholder = '', $minlength = 0, $class = '' , $style = '')
    {
        $disabled="";
        if($this->utils->contains('disabled', $class))$disabled='disabled';
        if($this->utils->contains('readonly', $class))$disabled='readonly';

        $GLOBALS[tabindex]++;
        if ($label=='') {
            //$label=$name;
            $label=$name;
        }
        $label=\util::l($label);
        $res="<label>$label</label>
            <input type='text' name='$name' value='$value' class='span12' placeholder='$placeholder'/ $disabled>";


        $res="
          <label class='control-label' for='$name'>$label</label>
            <input type='text' name='$name' id='text-$name' value='$value' placeholder='$placeholder' class='$class' tabindex='$GLOBALS[tabindex]' style='$style' $disabled>";
        if (($name=='email')&&($minlength>0)) {
            $GLOBALS['js']['form'].="   $name: {
              minlength: $minlength,
              email: true,
                        required: true
          },";
            $minlength=0;
        }

        if ($minlength>0) {
            $GLOBALS['js']['form'].="   $name: {
          minlength: $minlength,
          required: true
      },";
        }
        return $res;
    }
    function form_password($name = '', $value = '', $label = '', $placeholder = '', $minlength = 0, $properties, $class = '')
    {
        $disabled=($this->utils->contains('disabled', $class))?"disabled":"";
        $GLOBALS[tabindex]++;
        if ($label=='') {
            $label=$name;
        }
        $label=\util::l($label);
        $res="<label>$label</label>
            <input type='password' name='$name' value='$value' class='span12' placeholder='$placeholder' tabindex='$GLOBALS[tabindex]'/ $disabled>";

            $res="<label class='control-label' for='$name'>$label</label>
                  <input type='password' name='$name' id='$name' value='$value' class='$class' placeholder='$placeholder' $disabled>";
        if ($minlength>0) {
            $GLOBALS['js']['form'].="   $name: {
                  minlength: $minlength,
                  required: true
              },";
        }

        if ($name=='password_confirm') {
            $GLOBALS['js']['form'].="  $name: {
                      minlength: $minlength,
                                equalTo : \"#password\"
                  },";
        }

        return $res;
    }
    function form_date($name = '', $value = '', $label = '', $placeholder = '', $minlength = 0, $class = '')
    {
        $disabled=($this->utils->contains('disabled', $class))?"disabled":"";
        $GLOBALS[tabindex]++;
        if ($placeholder=='') {
            $placeholder='DD.MM.YYYY';
        }
        if ($label=='') {
            $label=$name;
        }
        $label=\util::l($label);
        $res="<label>$label</label>
                <input type='text' data-datepicker='datepicker' name='$name' value='$value'  class='$class'' placeholder='$placeholder'/ $disabled>";

        $res="<label class='control-label' for='$name'>$label</label>

              <input type='text' name='$name' id='$name' value='$value' placeholder='$placeholder' data-datepicker='datepicker'  class='$class' tabindex='$GLOBALS[tabindex]' $disabled>";
        if ($minlength>0) {
            $GLOBALS['js']['form'].="   $name: {
              minlength: $minlength,
              required: true
          },";
        }
        return $res;
    }
    function form_textarea($name = '', $value = '', $label = '', $placeholder = '', $minlength = 0, $properties = '', $class = '')
    {
        $disabled=($this->utils->contains('disabled', $class))?"disabled":"";
        $GLOBALS[tabindex]++;
        if ($label=='') {
            $label=$name;
        }
        $label=\util::l($label);
        if ($properties=='') {
            $rows='4';
        }
        $res="<label>$label</label>
            <textarea name='$name' class='' tabindex='$GLOBALS[tabindex]' $disabled>$value</textarea>";

        $res="
          <label class='control-label' for='message'>$label</label>
              <textarea name='$name' id='$name' $rows class='$class' placeholder='$placeholder' $properties tabindex='$GLOBALS[tabindex]' $disabled>$value</textarea>";
        if ($minlength>0) {
            $GLOBALS['js']['form'].="   $name: {
              minlength: $minlength,
              required: true
          },";
        }

        return $res;
    }
    function form_chekbox($name = '', $value = '', $label = '', $simple = 0, $hangout=0)
    {
        $disabled=($this->utils->contains('disabled', $class))?"disabled":"";
        $GLOBALS[tabindex]++;
        if ($label=='') {
            $label=$name;
        }
        $label=\util::l($label);
        if ($value>0) {
            $chkd='checked';
        } else {
            $value=strtolower((string)$value);
            if (($value=='t')||($value=='true')) {
                $chkd='checked';
            }
        }
        if($hangout>0){
            $showhint="onMouseover=\"showhint('$label', this, event, '150px');\"";
            $label='';
        }
        $res="<div style='clear:both;'></div><div class='checkbox inline'><input type='checkbox' name='$name' id='$name' value='1' $chkd tabindex='$GLOBALS[tabindex]' $disabled $showhint /><label> $label</label></div>";
        if ($simple==1) {
            $res="<input type='checkbox' name='$name' id='$name' value='1' $chkd $showhint /> $label";
        }
        return $res;
    }
    function form_chekboxFC($name = '', $value = '', $label = '', $negative = 0, $class = '')
    {
        $disabled=($this->utils->contains('disabled', $class))?"disabled":"";
        $GLOBALS[tabindex]++;
        $id=$GLOBALS[tabindex];
        if ($label=='') {
            $label=$name;
        }
        $label=\util::l($label);
        if ($value>0) {
            $chkd='checked';
        } else {
            $value=strtolower((string)$value);
            if (($value=='t')||($value=='true')) {
                $chkd='checked';
            }
        }
        if ($negative==1) {
            $no='No';
        }
        //$diasabled=($disabled>0)?'disabled':'';
        $res="<div class='bi7CheckboxList$no'>
    <input name='$name' id='bi7Checkbox_$id' type='checkbox' value='1' class='bi7Field' $chkd $disabled>
    <label for='bi7Checkbox_$id'>$label</label>
    </div>";

        return $res;
    }

    function form_json($json=''){
        $settings=json_decode($json, TRUE);
        foreach ($settings as $setting => $value) {
            $setting_arr=explode("_", $setting);
            //$value_type=$setting_arr[count($setting_arr)-1];
            $value_type=array_pop($setting_arr);
            $setting_name=implode("_", $setting_arr);
            $setting_name_clean=str_ireplace("_", " ", $setting_name);
            $fields[]=[
                'setting_name'=>$setting_name,
                'setting_name_clean'=>$setting_name_clean,
                'value_type'=>$value_type,
                'value'=>$value,
            ];

            if($value_type=='chk'){
                $out.=$this->form_chekbox("json_".$setting,$value,$setting_name_clean,'',0,'span12');
            }elseif(($value_type=='text')||($value_type=='num')){
                $out.=$this->form_text("json_".$setting,$value,$setting_name_clean,'',0,'span12');
            }
            if($value_type=='date'){
                $out.=$this->form_date("json_".$setting,$value,$setting_name_clean,'',0,'span12');
            }
            if($value_type=='area'){
                $out.=$this->form_textarea("json_".$setting,$value,$setting_name_clean,'',0,'','span12');
            }
        }
        $fileds_json=json_encode($fields);
        //$out.=$this->form_chekbox("json_test",1,'test_id','',0,'span12');
        return $out;
    }

    function save_settings($json){
        //echo $this->pre_display($json,"json");
        $settings=json_decode($json, TRUE);
        //echo $this->pre_display($settings,"settings");
        foreach ($settings as $setting => $value) {

            $setting_arr=explode("_", $setting);
            $value_type=$setting_arr[count($setting_arr)-1];
            if($value_type=='date'){
                $settings1[$setting]=$this->readRQd("json_".$setting);
            }elseif(($value_type=='text')||$value_type=='area'){
                $settings1[$setting]=$this->readRQ("json_".$setting);
            }elseif(($value_type=='chk')||$value_type=='num'){
                $settings1[$setting]=$this->readRQn("json_".$setting);
            }
        }

        $settings_json=json_encode($settings1);
        //echo $this->pre_display($settings_json,"settings_json");
        return $settings_json;
    }
    function boolean($value = '')
    {
        if (($value=='')||(strtolower($value)=='f')||(strtolower($value)=='false')||($value==0)) {
            $res="<span class='badge red'>✘</span>";
        } else {
            $res="<span class='badge green'>✔</span>";
        }
        return $res;
    }


    function file_form($link = '', $tablename = '', $refid = 0, $desc = '')
    {
        $res= "
            <div id='stylized' class='well'>
            <form action='$link' method='post' name='uploads' enctype='multipart/form-data' >
        <h1>Upload File $desc</h1>
        <p>Large file may take longer time</p>
        <input type='hidden' name='refid' value='$refid'>
            <input type='hidden' name='tablename' value='$tablename'>
            <label>File</label><input name='ufile' type='file' id='ufile' class='ufile'><br>
        <button type='submit' name='act' value='save' id='button' class='btn btn-primary'  onClick='document.getElementById(\"button\").innerHTML=\"Wait...\";' language='javascript'>Import</button>
        <div class='spacer'></div>
        </form>
        </div>";
        return $res;
    }


    function form_1col($col1 = 'Col1', $label = 'Collapsable', $collapsable = 0)
    {
        $in=$collapsable>0?'out':'in';
        $source=strtolower(str_ireplace(" ", "_", $label));
        $res="<label class='badge-top'><button type='button' class='btn btn-micro' data-toggle='collapse' data-target='#$source'><span class='icon-folder-open'></span></button> $label</label>
        <fieldset class='lookup collapse $in' id='$source'>
            <div class='row' style='margin-left:0px;'>
                <div class='span12'>
                    $col1
                </div>
            </div>
        </fieldset>";
        return $res;
    }

    function form_2cols($col1 = 'Col1', $col2 = 'Col2', $label = 'Collapsable', $collapsable = 0)
    {
        $in=$collapsable>0?'out':'in';
        $source=strtolower(str_ireplace(" ", "_", $label));
        $res="<label class='badge-top'><button type='button' class='btn btn-micro' data-toggle='collapse' data-target='#$source'><span class='icon-folder-open'></span></button> $label</label>
        <fieldset class='lookup collapse $in' id='$source'>
            <div class='row' style='margin-left:0px;'>
                <div class='span6'>
                    $col1
                </div>
                <div class='span6'>
                    $col2
                </div>
            </div>
        </fieldset>";
        return $res;
    }

    function cols2($col1 = 'Col1', $col2 = 'Col2', $label1 = '', $label2 = '')
    {
        if ($label1!='') {
            $label1="<label class='badge-top'>$label1</label>";
            $class1="lookup";
        }
        if ($label2!='') {
            $label2="<label class='badge-top'>$label2</label>";
            $class2="lookup";
        }
        $res.= "
        <fieldset>
        <div class='row' style='margin-left:0px;'>
            <div class='span6'>
                $label1
                <fieldset class='$class1'>$col1</fieldset>
            </div>
            <div class='span6'>
                $label2
                <fieldset class='$class2'>$col2</fieldset>
            </div>
        </div>
        </fieldset>
        ";
        return $res;
    }

    function cols_auto($cols=[], $labels = [])
    {
        $count=count($cols);
        $span=floor(12/$count);
        $i=0;
        foreach ($cols as $col) {
            $inside.="<div class='span$span'>

                <label class='badge-top'>$labels[$i]</label>
                <fieldset class='lookup'>$col</fieldset>
            </div>";
            $i++;
        }

        $res.= "
        <fieldset>
        <div class='row' style='margin-left:0px;'>
            $inside
        </div>
        </fieldset>
        ";
        return $res;
    }

    function tr_array($array = [], $class = '')
    {
//table row
        $out="<tr class='$class'>";
        foreach ($array as $item) {
            if (is_numeric($item)) {
                $out.="<td class='n'>".$this->money($item)."</td>";
            } else {
                $out.="<td>$item</td>";
            }
        }
        $out.="</tr>";
        return $out;
    }

    function label($label = '')
    {
        $label=ucwords(str_ireplace('_', ' ', $label));
        return $label;
    }
    function vpair($label = '', $value = '')
    {
        $label=$this->label($label);
        if (is_numeric($value)) {
            $value=$this->money($value);
        }
        $out="$label:<br><b>$value</b>";
        return $out;
    }

    function boxlist($lname = '', $sql = '', $sell = '', $all = '', $opts = '', $def = '')
    {
        $sel='';
        if (!($cur = pg_query($sql))) {
            echo "<div class='error'>".pg_last_error()."<br><b>".$sql."</b></div>" ;
        }

        $txt = "<div class='scroll_checkboxes well'>\n";
        $txt .= "<input type='hidden' name='boxlistname' value='$lname' class='checkbox'>\n";
        while ($line = pg_fetch_array($cur)) {
            $id =   $line[0];
            $name=$line[1];
            $sel = '';
            if (($id == $sell)&&($sell<>'')) {
                $sel = 'SELECTED';
            }
            if (($id == $def)&&($def<>'')&&($sell=='')) {
                $sel = 'SELECTED';
            }
            //if (length($name) > 40){$name="...".substr($name,0,15).substr($name,length($name)-15,15);}
            $txt = "$txt<dt><input type='checkbox' name='$lname-$id' value='1' class='checkbox'/>$name</dt>\n";
        }
        $txt = "$txt</div>\n";
        return $txt;
    }

    function btnlist($lname='',$sql='',$sell='',$all='', $opts='',$def=''){
        $out.="<div class='btn-group' data-toggle='buttons'>";
        if (!($cur = pg_query($sql))) {echo "<div class='error'>".pg_last_error()."<br><b>".$sql."</b></div>" ;}
        while ($line = pg_fetch_array($cur)) {
            $id =   $line[0];
            $name=$line[1];
            $style=$line[2];
            
        //foreach($vals as $value){
            $color="";
            $active="";
            
            if($id==$sell){$color="btn-info"; $active='checked';}

            $out.="<label class='btn btn-mini btn-default $active $color'>
                                <input type='radio' name='$lname' value='$id' style='$style' $active> $name </label>";
        }
        $out.="</div>";
        return $out;
    }

    function htlist($lname = '', $sql = '', $sell = '', $all = '', $opts = '', $def = '', $class = '')
    {
        $GLOBALS[tabindex]++;
        $sel='';
        if (!($cur = pg_query($sql))) {
            echo "<div class='error'>".pg_last_error()."<br><b>".$sql."</b></div>" ;
        }

        $txt = "<SELECT NAME='$lname' class='ui-widget ui-state-default ui-corner-all $class' ID='id_$lname' tabindex='$GLOBALS[tabindex]' $opts>\n";
        if ($all <> '') {
            $txt = "$txt<OPTION SELECTED VALUE='$def'>$all</OPTION>\n";
        } else {
            // $txt = "$txt<OPTION SELECTED VALUE='$sell'>$all</OPTION>\n";
        }
        while ($line = pg_fetch_array($cur)) {
            $id =   $line[0];
            $name=$line[1];
            $style=$line[2];
            $sel = '';
            if (($id == $sell)&&($sell<>'')) {
                $sel = 'SELECTED';
            }
            if (($id == $def)&&($def<>'')&&($sell=='')) {
                $sel = 'SELECTED';
            }
            $name=str_ireplace(" ","",$name);
            $name=trim($name);
            //if (length($name) > 40){$name="...".substr($name,0,15).substr($name,length($name)-15,15);}
            $txt = "$txt<OPTION $sel VALUE='$id' style='$style'>$name</OPTION>\n";
        }
        $txt = "$txt</SELECT>\n";
        return $txt;
    }
    function dropdown_filed($caption = '', $filed = '', $table = '', $defaultvalue = '', $hidden = '')
    {
        if ($hidden!='') {
            $class="hidden";
        }
        $out.="";
        $sql="select distinct $filed, $filed from $table where $filed!='' union select 'None', 'None'";
        $htlist=$this->htlist("select_$filed", $sql, $defaultvalue, "Select $caption", "onchange='document.getElementById(\"id_$filed\").value=this.options[this.selectedIndex].value;'");
        $out.="<dl><dt class='$class'><label>$caption</label><input type='text' name='$filed'  id='id_$filed' value='$defaultvalue'></dt>
        <dt>$htlist</dt></dl>";
        return $out;
    }
    function dropdown_list($caption = '', $filed = '', $list = '', $defaultvalue = '', $hidden = '')
    {
        if ($hidden!='') {
            $class="hidden";
        }
        $listarray=explode(",", $list);
        $htlist = "<select NAME='$filed' class='ui-widget ui-state-default ui-corner-all' ID='id_$filed'>\n";
        $htlist = "$htlist<OPTION VALUE=''>None</OPTION>\n";
        foreach ($listarray as $item) {
            $sel=$defaultvalue==$item?"selected":"";
            if ($item!='') {
                $htlist = "$htlist<OPTION $sel VALUE='$item'>$item</OPTION>\n";
            }
        }
        $htlist = "$htlist</SELECT>\n";
        $out.="";
        $caption=\util::l($caption);
        $out.="<dl><dt class='$class'><label>$caption</label>$htlist</dt></dl>";
        return $out;
    }

    function dropdown_list_array($caption = '', $filed = '', $list = [], $defaultvalue = '', $hidden = '')
    {
        if ($hidden!='') {
            $class="hidden";
        }

        $htlist = "<select NAME='$filed' class='ui-widget ui-state-default ui-corner-all' ID='id_$filed'>\n";
        $htlist = "$htlist<OPTION VALUE=''>None</OPTION>\n";

        foreach ($list as $key => $value) {
            $sel=$defaultvalue==$value?"selected":"";
            if ($key!='') {
                $htlist = "$htlist<OPTION $sel VALUE='$value'>$key</OPTION>\n";
            }
        }
        $htlist = "$htlist</SELECT>\n";
        $out.="";
        $caption=\util::l($caption);
        $out.="<dl><dt class='$class'><label>$caption</label>$htlist</dt></dl>";
        return $out;
    }


    public function collapse($content = '', $title = '', $collapsed = true)
    {
        if ($collapsed) {
            $in='in';
        }
        $class=preg_replace("/[^a-z0-9]+/i", "", $title);
        return "<label class='badge-top'><button type='button' class='btn btn-micro' data-toggle='collapse' data-target='#collapsable_$class'><span class='icon-folder-open'></span></button>
        $title</label>
        <div class='lookup collapse $in' id='collapsable_$class' >
            <div class='info'>$content</div>
        </div>";
    }

    public function collapsable($content = '', $title = '', $collapsed = true, $class = '', $header = '', $footer = '', $icon = 'icon-folder-open', $full=0)
    {
        if ($collapsed) {
            $in='in';
        }
        $collapsable_class=($full==1)?'collapsablefull':'collapsable';
        $class_id=preg_replace("/[^a-z0-9]+/i", "", $title);

        $label= "<label class='badge-collapsable $class'><button type='button' style='margin-left:10px;' class='btn btn-micro' data-toggle='collapse' data-target='#collapsable_$class_id'><span class='$icon'></span></button> $title</label>";

        if ($header) {
            $header="<div class='collapse-header'>$header</div>";
        }
        if ($footer) {
            $footer="<div class='collapse-footer'>$footer</div>";
        }
        $out.="<div class='$collapsable_class'>$label
        <div class='$collapsable_class $class'>
        $header
        <div class='collapse $class $in' id='collapsable_$class_id' >
            <div class='inside-collapse-disable'>$content</div>
        </div>
        $footer
        </div>
        </div>";
        return $out;
    }

    function table($rows = '', $settings = [])
    {
        $out="<table>$rows</table>";
        return $out;
    }
    function notFound($message = '')
    {
        header("HTTP/1.0 404 Not Found");
        echo '<h2>404 Not found</h2><p>'.$message.'</p>';
    }

    public function wrap_string($string, $chars='80'){
        $string=wordwrap($string, $chars, "<br />\n");
        return $string;
    }
    public function wrappedMessage($message = '', $title = '', $class = 'alert-info')
    {
        $this->putHeader();
        $this->putWrap_in();
        echo $this->message($message, $title, $class);
        $this->putWrap_out();
        $this->putFooter();
        exit;
    }
    public function putErorMessage()
    {
        if ($GLOBALS[error_message]!='') {
            echo "<div class='error red alert alert-error'>ERROR:<br>$GLOBALS[error_message]</div>";
        }
        if ($_POST[error_message]!='') {
            echo "<div class='error red alert alert-error'>ERROR:<br>$_POST[error_message]</div>";
        }
        if ($_GET[error_message]!='') {
            echo "<div class='error red alert alert-error'>ERROR:<br>$_GET[error_message]</div>";
        }
    }
    public function putInfoMessage()
    {
        if ($GLOBALS[info_message]!='') {
            echo "<div class='well'>$GLOBALS[info_message]</div>";
        }
        if ($_POST[info_message]!='') {
            echo "<div class='well'>$_POST[info_message]</div>";
        }
        if ($_GET[info_message]!='') {
            echo "<div class='well'>$_GET[info_message]</div>";
        }
    }
    public function putDebugMessage()
    {
        if ($GLOBALS[debug_message]!='') {
            echo "<div class='well'>$GLOBALS[debug_message]</div>";
        }
        if ($_POST[debug_message]!='') {
            echo "<div class='well'>$_POST[debug_message]</div>";
        }
        if ($_GET[debug_message]!='') {
            echo "<div class='well'>$_GET[debug_message]</div>";
        }
    }

    public function modal($data = [])
    {
        $modal_id='modal_'.uniqid();
        if ($data[action]!='') {
            $form_start="<form method='POST' action='$data[action]' class='' id='$modal_id' name='save-$modal_id'>\n";
            $form_end="</form>";
            $ok_btn="<button type='submit' class='btn btn-primary'>Submit</button>";
        }
        foreach ($data[fields] as $field) {
            $ins="";
            if ($field=='date') {
                $ins=$this->form_date('date', '', 'Date', '', 0, 'span12');
            }
            if ($field=='df') {
                $ins=$this->form_date('df', '', 'Date from', '', 0, 'span12');
            }
            if ($field=='dt') {
                $ins=$this->form_date('dt', '', 'Date to', '', 0, 'span12');
            }
            if ($field=='descr') {
                $ins=$this->form_textarea('descr', '', 'Description', '', 0, '', 'span12');
            }
            if ($field=='text') {
                $ins=$this->form_textarea('text', '', 'Text', '', 0, '', 'span12');
            }
            if ($field=='password') {
                $ins=$this->form_password($field, "", ucfirst($field), '', 0, 'span12');
            }
            if ($field=='active') {
                $ins=$this->form_chekbox($field, '', ucfirst($field), '', 0, 'span12');
            }
            if ($field=='ok') {
                $ins=$this->form_chekbox($field, '', ucfirst($field), '', 0, 'span12');
            }
            if ($ins=='') {
                $ins=$this->form_text($field, "", ucfirst($field), '', 0, 'span12');
            }
            $data[html].=$ins;
        }
        foreach ($data[hidden] as $field_name => $field_value) {
            $data[html].=$this->form_hidden($field_name, $field_value);
        }
        if ($data['class']=='') {
            $data['class']='btn-micro';
        }
        if ($data['class']!='link') {
            $btn="<a href='#{$modal_id}' role='button' class='btn ".$data['class']."' data-toggle='modal'>$data[label]</a>";
        } else {
            $btn="<a href='#{$modal_id}' role='button' class='' data-toggle='modal'>$data[label]</a>";
        }

        $modal="
    <!-- Modal -->
    <div id='{$modal_id}' class='modal hide fade left' style='text-align: left; align:left;' tabindex='-1' role='dialog' aria-labelledby='{$modal_id}Label' aria-hidden='true'>
      $form_start
      <div class='modal-header'  class='left'  style='text-align: left; align:left;'>
        <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>×</button>
        <h3 id='{$modal_id}Label'>$data[header]</h3>
      </div>
      <div class='modal-body'>
        <p>$data[before]</p>
        <p>$data[html]</p>
        <p>$data[after]</p>
      </div>
      <div class='modal-footer'>
        <button class='btn' data-dismiss='modal' aria-hidden='true'>Close</button>
        $ok_btn
      </div>
      $form_end
    </div>";
        return [btn=>$btn,modal=>$modal];
    }
    public function putTopBar($menu = '', $userinfo = '', $other = '')
    {
            global $gid, $access;
            $access[report_searchresults]=1;
        if (($access[report_searchresults])&&($menu!='')&&($GLOBALS['settings']['no_search']=='')) {
            $search="<form class='navbar-search' action='?act=report&what=searchresults' method='post'>
                <input type='text' autocomplete='off' class='search-query' placeholder='Search' name='text'>
                </form>";
        }
        if ($access[main_print]) {
            $print.="<a href='?$GLOBALS[query]&print=1' class='icon-print icon-white' style='margin-top:10px;' target=''></a>";
        }
        if (($GLOBALS[topbar][login]>0)&&($gid>0)&&($GLOBALS['settings']['app_loogo']=='')) {
            $print.="<a href='?' class='icon-home icon-white' style='margin-top:10px;' target=''></a>";
        }

        if (($GLOBALS[topbar][login]>0)&&($gid<1)) {
            $userinfo.="<span><a href='?act=welcome' style='margin-top:0px;' target=''><li class='icon-user icon-white'></li><span style='color:#fff;'> Login</span></a></span>";
        }



        if ($GLOBALS['settings']['app_loogo']!='') {
            $logo="<a href='?act='><span style=\"margin-top:10px; margin-left:20px;display: block;\">".$GLOBALS['settings']['app_loogo']."</span></a>";
        }

        if ($GLOBALS['settings']['message']!='') {
            $msg="<span style=\"margin-top:10px; margin-left:20px;display: block;\">".$GLOBALS['settings']['message']."</span>";
        }


        if ($GLOBALS['settings']['user_info']!='') {
            $userinfo=$GLOBALS['settings']['user_info']." | ".$userinfo;
        }


            $out.= '
            <div media="print"  class="noPrint">
            <div class="navbar navbar-inverse">
                  <div class="navbar-inner">
                    <div class="container-fluid">
                      <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                      </a>

                      <div class="nav-collapse">
                        <ul class="nav">
                          '.$logo.'
                        </ul>
                        <ul class="nav">
                          '.$menu.'
                        </ul>
                        <ul class="nav ">
                          '.$search.'
                        </ul>
                        <ul class="nav ">
                          '.$print.'
                        </ul>
                        <ul class="nav ">
                          '.$readme.'
                        </ul>
                        <div class="nav">
                        '.$msg.'
                        </div>
                        <div class="nav navbar-text pull-right">
                        '.$userinfo.'
                        </div>

                      </div><!--/.nav-collapse -->
                    </div>
                  </div>
                </div>
                </div>
                '.$other;
        echo $out;
    }

    function add_button($what = '', $morelink = '')
    {
        $addform = $this->readRQ('addform');
        $noadd = $this->readRQn('noadd');
        $reference = $this->readRQ('reference');
        $reffinfo = $this->readRQ('reffinfo');
        $addlink = $this->readRQ('addlink');
        $refid = $this->readRQn('refid');
        $type = $this->readRQn('type');
        $category = $this->readRQ('category');

        $suffix=substr($what, 0, 3);

        if (!($suffix=='vw_')) {
            $addbutton="<a href='?act=add&what=$what$addlink&type=$type&refid=$refid&reference=$reference&category=$category$reffinfo$morelink'><i class='icon-plus-sign tooltip-test addbtn' data-original-title='Create new'></i></a>";
        }
        if ($addform!="") {
            $addbutton="<a href='?act=add&table=$addform$addlink&type=$type&refid=$refid&reference=$reference&category=$category$reffinfo$morelink'><i class='icon-plus-sign tooltip-test addbtn' data-original-title='Create new'></i></a>";
        }
        if ($addform=="-") {
            $addbutton="";
        }
        if ($noadd>0) {
            $addbutton="<i class='' style='margin-right:26px;' data-original-title=''></i>";
        }
        return $addbutton;
    }

    function add_button2($what = '', $morelink = '')
    {
        $suffix=substr($what, 0, 3);
        if (!($suffix=='vw_')) {
            $addbutton="<a href='?act=add&what=$what$morelink'><i class='icon-plus-sign tooltip-test addbtn' data-original-title='Create new'></i></a>";
        }
        return $addbutton;
    }

    function title($title = '', $srchbtn = '')
    {
        return "<h3 class='foldered'>$srchbtn".ucfirst($title)."</h3>";
    }
    function add_all_to_cart($ids = '')
    {
        $res= "<span media='print' class='noPrint'><i class='btn btn-small' onClick=\"this.className='blackout';basket_additem('$ids')\">Add all to cart <i class='icon-shopping-cart'></i></i></span>";
        return $res;
    }
    function add_all_to_cart2($what = '')
    {
        if (!$GLOBALS['settings']['no_cart']) {
            return "<div class='btn btn-mini cart-addall' reference='$what'>Add all <i class='icon-shopping-cart'></i></div>";
        } else {
            return;
        }
    }

    function show_hide($name = '', $link = '', $opt = '')
    {
        $name=str_replace(" ", "_", $name);
        $showname=str_replace("_", " ", $name);
        $link="$link&dynamic=1&title=$name";
        $html_link=str_replace("&dynamic=1", "", $link);
        $html_link=str_replace("&plain=1", "", $html_link);
        $reload='<div id="icon-refresh-'.$name.'"
        hidden
        onclick="
        reload_controls();
        $(this).hide();
        " class="reload-controlls"><i class="icon-refresh"></i></div>';
        $with_link='';
        if ($this->utils->contains('with_link', strtolower($opt))) $with_link='<a href="'.$html_link.'">-></a>';
        if ($opt!='inline') {
            $buttonhide='<button class=\"btn '.$opt.'\">Hide '.$showname.'<span class=\"caret\"></span></button>';
            $buttonshow='<button class=\"btn '.$opt.'\">Show '.$showname.'<span class=\"caret\"></span></button>';
            $buttonshow1='<button class="btn '.$opt.'">Show '.$showname.'<span class="caret"></span></button>';
        } else {
            //$buttonhide='<i class="icon-folder-open"></i>';
            ///$buttonshow='<i class="icon-folder-close"></i>';
            //$buttonshow1='<i class="icon-folder-close"></i>';
                $buttonhide='<button class=\"btn btn-micro '.$opt.'\"><span class=\"icon-folder-open\"></span></button>';
                $buttonshow='<button class=\"btn btn-micro '.$opt.'\"><span class=\"icon-folder-close\"></span></button>';
                $buttonshow1='<button class="btn btn-micro '.$opt.'"><span class="icon-folder-close"></span></button>';
        }

        //$buttonhide="Hide $name";
        //$buttonshow="Show $name";
        $text="<div id='$name.act_'
        onclick='ajaxFunction(\"$name.\",\"$link\");
                document.getElementById(\"$name.act_\").innerHTML=\"\";
                document.getElementById(\"$name.hide_\").innerHTML=\"$buttonhide\";
                $(\"#icon-refresh-$name\").show();
                //reload_controls();
                setTimeout(function(){reload_controls();},3000);
                '
            onmouseover=\"this.style.cursor='pointer';\">$buttonshow1</div>
            <div id='$name.hide_'
            onclick='document.getElementById(\"$name.act_\").innerHTML=\"$buttonshow\";
                     document.getElementById(\"$name.hide_\").innerHTML=\"\";
                     document.getElementById(\"$name.\").innerHTML=\"\";'
                onmouseover=\"this.style.cursor='pointer';\"></div>$reload2
                <div id='$name.'></div>";
        return "<div class='well-fw'>$text</div><br>$with_link";
    }
    function refreshpage($where = '', $time = 0, $message = '')
    {
        global $refreshtime,$reflink;
        if(($_GET[act]=='api')||($_POST[act]=='api')) return json_encode(['refresh'=>strip_tags($message)]);
        if ($GLOBALS[no_refresh]!=1) {
            if ($time>0) {
                $refreshtime=$time;
            }
            //$headrefresh=1;
            $refresh=$this->readRQn('refresh');
            if ($refresh>0) {
                $headrefresh=0;
            }
            //if($refreshtime==0)$refreshtime=1;
            if ($where=='') {
                $where=$reflink;
            }
            if (strlen($where)<2) {
                $where="?";
            }
            if ($headrefresh==1) {
                header("Location: $where");
            } else {
                $miliseconds=$time*1000+1000;
                //header("Location: $where");
                $out.= "<META HTTP-EQUIV=\"refresh\" CONTENT=\"$refreshtime; URL=$where\">";
                $out.= "<div style='background: url(\"".ASSETS_URI."/assets/img/progressbar.gif\"); margin-top:10px; width=100%;'> $time seconds to display this message..<a href='$where'>.</a></div>";
                $out.= "<div align='left'>";
                $out.= "$message";
                $out.= "</div>
                <script>
                setTimeout(function(){window.location = '$where';},$miliseconds);
                </script>
                ";
            }
        }

        return $out;
    }
    function print_book($sheets = '')
    {
        $out='<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Multiple Sheets</title>

  <!-- Normalize or reset CSS with your favorite library -->
  <link rel="stylesheet" href="'.ASSETS_URI.'/assets/css/normalize.css">

  <!-- Load paper.css for happy printing -->
  <link rel="stylesheet" href="'.ASSETS_URI.'/assets/css/paper.css">

  <!-- Set page size here: A5, A4 or A3 -->
  <!-- Set also "landscape" if you need -->

  <style>@page { size: A4 }</style>
</head>

<!-- Set "A5", "A4" or "A3" for class name -->
<!-- Set also "landscape" if you need -->
<body class="A4">

  '.$sheets.'

</body>

</html>';
        return $out;
    }

    function print_sheet($html = '')
    {
        $out='<!-- Each sheet element should have the class "sheet" -->
  <!-- "padding-**mm" is optional: you can set 10, 15, 20 or 25 -->
  <section class="sheet padding-20mm page">
  <div class="subpage">

  '.$html.'
  </div>
  </section>';
        return $out;
    }

    function link_button($title = '', $link = '', $class = '', $warn = '')
    {
        $title=\util::l($title);
        $warn=\util::l($warn);
        //$out="<a href='$link'><i class='btn btn-mini $class'>$title</i></a>";
        $link=htmlspecialchars($link);
        if ($warn=='') {
            $out="<a href='$link'><span class='btn $class' type='button'>$title</span></a>";
        } else {
            $out="<span class='btn $class' onclick=\"confirmation('$link','$warn');\" style=\"cursor: pointer; cursor: hand; \">$title</span>";
        }
        return $out;
    }

    function link_badge($title = '', $link = '', $class = '', $warn = '')
    {
        $title=\util::l($title);
        $warn=\util::l($warn);
        //$out="<a href='$link'><i class='btn btn-mini $class'>$title</i></a>";
        $link=htmlspecialchars($link);
        if ($warn=='') {
            //$out="<span onclick=\"location.href = '$link';\" style=\"cursor: pointer; cursor: hand; \" class='badge badge-$class'>$title</span>";
            $out="<a href=\"$link\"><span class='label label-$class'>$title</span></a>";
        } else {
            $out="<span onclick=\"confirmation('$link','$warn');\" style=\"cursor: pointer; cursor: hand; \" class='badge badge-$class'>$title</span>";
        }
        return $out;
    }

    function link_label($title = '', $link = '', $class = '', $warn = '')
    {
        $title=\util::l($title);
        $warn=\util::l($warn);
        //$out="<a href='$link'><i class='btn btn-mini $class'>$title</i></a>";
        $link=htmlspecialchars($link);
        if ($warn=='') {
            //$out3="<span onclick=\"location.href = '$link';\" style=\"cursor: pointer; cursor: hand; \" class='label label-$class'>$title</span>";
            $out="<a href=\"$link\"><span class='label label-$class'>$title</span></a>";
        } else {
            $out="<span onclick=\"confirmation('$link','$warn');\" style=\"cursor: pointer; cursor: hand; \" class='label label-$class'>$title</span>";
        }
        return $out;
    }

    function confirm_with_comment($title = '', $link = '', $class = '', $warn = '')
    {
        $title=\util::l($title);
        $warn=\util::l($warn);
            $out="<span class='btn btn-mini btn-$class' onclick=\"leavecomment('$link','$warn');\" style=\"cursor: pointer; cursor: hand; \">$title</span>";
        return $out;
    }
    function confirm_action($title = '', $link = '', $class = '', $warn = '')
    {
        $title=\util::l($title);
        $warn=\util::l($warn);
            $out="<span class='btn btn-mini btn-$class' onclick=\"confirmation('$link','$warn');\" style=\"cursor: pointer; cursor: hand; \">$title</span>";
        return $out;
    }

    function display_changes($array = [])
    {

        $tbl.=$this->tablehead('', '', '', '', ['#','date','uid','username','ip','category'], 'autosort');

        foreach ($array as $key => $value) {
            $d=$this->array_nested_display($value[change], "now_chnges");

            $d=str_ireplace("'", "\'", $d);
            $d=str_ireplace("\n", "", $d);
            $tbl.="<tr><td ckass='n' onMouseover=\"showhint('$d', this, event, '1500px')\">$value[no]</td>";
            $tbl.="<td>$value[date]</td>";
            $tbl.="<td>$value[uid]</td>";
            $tbl.="<td>$value[username]</td>";
            $tbl.="<td>$value[ip]</td>";
            $tbl.="<td>$value[category]</td>";
            //$tbl.="<td><textarea>$d</textarea></td>";
            $tbl.="</tr>";
        }
        $tbl.="</table>";
        return $tbl;
    }

    function array_display2_textarea($array = [], $title = '')
    {
        //echo $this->pre_display($array, $title);
        $i=0;
        $tbl.="\$vals=[\n";
        foreach ($array as $key => $val) {
            $tbl.="\t'$key'=>'$val',\n";
            $i++;
        }
        $tbl.="];";
        $tbl=$this->utils->exportcsv($tbl);
        return $tbl;
    }

    function array_display2DPlain($array = [], $title = '')
    {
        if (is_object($array)) {
            $array=get_object_vars($array);
        }
        //echo $this->pre_display($array, $title);
        $i=0;

        $tbl.="<table>";
        foreach ($array as $key => $val) {
            if (is_object($val)) {
                $val=get_object_vars($val);
            }
            $tbl.="<tr><td>$key</td><td>$val</td></tr>";

            $i++;
            //echo $this->pre_display($key,$val);
        }
        $tbl.="</table>";
        return $tbl;
    }

    function array_display2Dwiki($array = [], $title = '', $wiki_prefix = '')
    {
        //
        //echo $this->pre_display($array, $title);
        $i=0;

        $tbl.="<table class='table table-morecondensed table-notfull'>";
        foreach ($array as $key => $val) {
            $tokens=explode('_', $key);
            $wiki_page=strtolower($tokens[0]);
            $name="<a href='?act=report&what=doku&dokupage=$wiki_prefix$wiki_page' target='_blank'>$key</a>";
            $tbl.="<tr><td>$name </td><td> $val</td></tr>";

            $i++;
            //echo $this->pre_display($key,$val);
        }
        $tbl.="</table>";
        return $tbl;
    }

    function array_display2D($array = [], $title = '', $max_lenght=0, $align_left='')
    {
        //echo $this->pre_display($array, $title);
        $i=0;
        $fields=array('#','Key','Value');
        $head=$this->tablehead('', '', '', '', $fields);
        //$head="<table>";
        foreach ($array as $key => $val) {
            if($align_left==''){
                if (is_numeric($val)) {
                    $class='n';
                } else {
                    $class='';
                }
            }
            if ($this->utils->isJSON($val)) {
                $array=json_decode($val,true);
                $val=$this->array_nested_display($array,$max_lenght);
                $isJSON=true;
            }else{
                $val=htmlspecialchars($val);
            }

            if(($max_lenght>0)&&(!$isJSON))$val=$this->utf8->utf8_cutByPixel($val, $max_lenght, false);
            $rows.= "<tr class=''>";
            $rows.="<td class='n'>$i</td>";
            $rows.="<td class=''><b>$key</b></td>";
            $rows.= "<td class='$class'>$val</td>";
            $rows.= "</tr>";
            $i++;
            //echo $this->pre_display($row,$row_key);
        }

        $end="</table>";
        if ($title!='') {
            $title=$this->tag($title, 'foldered');
        }
        $tbl=$title.$head.$rows.$end;
        return $tbl;
    }

    function array_nested_display($array = [],$max_lenght=0)
    {
        $out= "<table class='table table-bordered table-morecondensed table-notfull' >";
        foreach ($array as $key => $value) {
            $out.= "<tr><td class='n'>{$key}:</td><td>";
            if (is_array($value)) {
                $out.=$this->array_nested_display($value, $max_lenght);
            } else {
                if(($max_lenght>0))$value=$this->utf8->utf8_cutByPixel($value, $max_lenght, false);
                $out.= "<b>$value</b>";
            }
            $out.= "</td></tr>";
        }
        $out.= "</table>";

        //$out="<table class='table table-bordered table-morecondensed table-notfull' ><tr><td class='n'>Test</td></tr></table>";
        return $out;
    }
    function array_values_form($array = [], $file_name = '', $where = '')
    {
        $form_opt['well_class']="span11 columns form-wrap";
        $form_opt['title']="Edit $file_name";
        $out.=$this->form_start('file_json','','',$form_opt);
        $out.=$this->form_hidden('file_name',$file_name);
        $out.=$this->form_hidden('where',$where);
        $out.="<hr>";
        $out.= "<table class='table table-bordered table-morecondensed table-notfull' >";
        foreach ($array as $key => $value) {
            $out.= "<tr><td class='n bold'>{$key}:</td><td class='span12'>";
            $out.=$this->form_text($key,$value,' ','',0,'span12');
            $out.= "</td></tr>";
        }
        $out.= "</table>";
        $out.=$this->form_submit('Save');
        $out.=$this->form_end();
        $result[out]=$out;
        return $result;
    }
    function array_nested_form($array = [], $table = '', $field = '', $id = 0, $parent = '', $js = '')
    {
        $inline_edit=1;
        if ($parent=='') {
            $parent=$table;
        }
        $out= "<table class='table table-bordered table-morecondensed table-notfull' >";
        foreach ($array as $key => $value) {
            $out.= "<tr><td class='n'>{$key}:</td><td>";
            //$parent=$key;
            //$trail="$trail->{$key}";
            $domain="$parent->$key";
            $domain_js=str_ireplace('->', '-', $domain);
            if (is_array($value)) {
                $res=$this->array_nested_form($value, $table, $field, $id, $domain, $js1);
                $out.=$res[out];
                $js1.=$res[js];
            } else {
                if ($inline_edit>0) {
                    $submitdata=array(
                        'table'=>$table,
                        'field'=>$field,
                        'is_json'=>1,
                        'domain'=>$domain,
                        'id'=>$id,
                    );

                    $class="#$domain_js";
                    $js1.=$this->utils->inline_js($class, $submitdata);
                    $value= "<span class='bold' id='$domain_js'>$value</span>";
                } else {
                }
                $out.= "<b>$value</b>";
            }
            $out.= "</td></tr>";
        }
        $out.= "</table>";
            $result[out]=$out;
            $result[js]=$js1;

        //$out="<table class='table table-bordered table-morecondensed table-notfull' ><tr><td class='n'>Test</td></tr></table>";
        return $result;
    }

    function array_display($array = [], $title = '', $rounding = '')
    {
        //echo $this->pre_display($array, $title);

        $i=0;
        $fields=[];
        array_push($fields, '#');
        foreach ($array as $row_key => $row) {
            $j=$i+1;
            $row_vals='';
            $row_class='';

            //echo $this->pre_display($row, $row_key);
            $c=0;
            foreach ($row as $col_key => $col) {
                $c++;
                $field_name=$col_key;

                $field_value=$col;

                if (!is_string($field_name)) {
                    $field_name="N_$field_name";
                }
                //if(is_numeric())
                //echo "$field_name = $field_value ($field_type)<br>";

                if ($field_name!='') {
                    //array_unshift($fields, $field_name);
                    array_push($fields, $field_name);
                    $class='';
                    if (is_numeric($field_value)) {
                        $class='n';
                    }
                    if (($rounding!='')&&(is_numeric($field_value))) {
                        $field_value=$this->money($field_value, '', '', $rounding);
                    }
                    $row_vals.="<td class='$class'>$field_value</td>";
                    if (($this->utils->contains('total', strtolower($field_value)))&&($c==1)) $row_class='c';
                }
            }

            if ($i==0) {
                //echo $this->pre_display($fields, 'fields');
                $head=$this->tablehead('', '', '', '', $fields);
            }
            if($row_class!='c'){
                $rows.= "<tr class='$row_class'>";
                $rows.="<td class='n'>$j</td>";
                $rows.= $row_vals;
                $rows.= "</tr>";
            }else{
                $rows.="</tbody><tfoot>";
                $rows.= "<tr class='$row_class'>";
                $rows.="<td class='n'> </td>";
                $rows.= $row_vals;
                $rows.= "</tr>";
                $rows.="</tfoot>";
            }


            //$sql_insert="INSERT INTO $tablename VALUES (NULL,";
            //$sql_insert.=$sql_insert_values;
            //$sql_insert=rtrim($sql_insert,",");
            //$sql_insert.=");\n";
            //$this->sql->exec($sql_insert) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql_insert);
            //echo $this->pre_display($sql_insert,'sql_insert');
            //$sql_inserts.=$sql_insert;
            $i++;
            //echo $this->pre_display($row,$row_key);
        }

        $end="</table>";
        if ($title!='') {
            $title=$this->tag($title, 'foldered');
        }
        $tbl=$title.$head.$rows.$end;
        return $tbl;
    }


    function post($post = [])
    {
        $name=$this->tag($post[name], 'b', '');
        //$name=$this->tag($name,'div','span3');
        $user=$this->tag("by $post[user]", 'span', 'd');
        $date=$this->tag($post[date], 'span', '');

        $rows=array($name,$user,$date);
        $title=$this->row($rows, 'middle');

        //$post_body.=$this->tag($title,'span','');
        $post_body.=$this->tag($post[text], 'pre', 'span12 message');
        $out.=$this->tag($post_body, 'div', 'container');
        //$post[user_id]=1;
        $button=$this->link_button('Reply', "?act=add&what=posts&ref_table=posts&ref_id=$post[id]", 'info');
        if ($post[user_id]==$GLOBALS[uid]) {
            $dell=$this->link_button('Delete', "?csrf=$GLOBALS[csrf]&act=delete&what=posts&id=$post[id]", 'danger', 'Are you sure?');
        }
        if ($post[user_id]==$GLOBALS[uid]) {
            $edit=$this->link_button('Edit', "?act=edit&what=posts&id=$post[id]", 'defalult');
        }
        $out="<div class='reply'>
                <div class='row-fluid post-header'>
                    <div class='span9'>
                        $name $user
                    </div>
                    <div class='span3 pull-right' style='text-align:right; padding-right: 15px;'>
                        $date
                    </div>
                </div>
                <div class='row-fluid'>
                    <div class='span12 '>
                        $post_body
                    </div>
                </div>
                <div class='row-fluid'>
                    <div class='span12'>
                        $button $edit $dell
                    </div>
                </div>
            </div> ";
        return $out;
    }

    function pairs($array = [], $title = '', $class = '')
    {
        $result=$this->tag($title, 'h3');
        $result.=$this->tablehead();
        foreach ($array as $key => $value) {
            $result.="<tr><td>$key</td><td class='$class'>$value</td></tr>";
        }
        $result.=$this->tablefoot();
        return $result;
    }

    function qrcode($url = '')
    {
        include(FW_DIR.'/classes/BarcodeQR.php');
        $qr = new BarcodeQR();
        $qr->url($url);
        $qr->draw();
        //$file=DATA_DIR."/docs/".uniqid().".png";
        //$qr->draw(300, $file);
        //if(file_exists($file)){
            //$imagefile = $file;


            //echo "ok";
            //unlink($file);
        //}
    }
    function rating_color($rating = '')
    {
        if ($rating=="A") {
            $r_class='badge-success';
        }
        if ($rating=="B") {
            $r_class='badge-warning';
        }
        if ($rating=="C") {
            $r_class='badge-important';
        }
        return $this->tag($rating, 'span', "badge $r_class");
    }

    public function link($query = '')
    {
        if (!is_array($query)) {
            $query=htmlspecialchars_decode($query);
            $query = ltrim($query, '?');
            parse_str($query, $array);
        } else {
            $array=$query;
        }
        //echo $this->pre_display($array,'from link func');
        $link='?'.http_build_query($array, '', '&amp;');
        return $link;
    }


    public function href($link = '', $label = 'link', $class = '')
    {
        $href='<a href="'.$link.'" class="'.$class.'">'.$label.'</a>';
        return $href;
    }



    function SQL_error($sql = '')
    {
        $connection_status = @pg_connection_status();
        $last_error = @pg_last_error();
        $result_error = @pg_result_error();
        $last_notice = @pg_last_notice();

        $_errors = array();

        $_errors[] = ($connection_status?$connection_status:'');
        $_errors[] = ($last_error?$last_error:'');
        $_errors[] = ($result_error?$result_error:'');
        $_errors[] = ($last_notice?$last_notice:'');

        $errors=implode("\n", $_errors);

        $msg=$this->pre_display($sql."\n".$errors, 'SQL error', 'red');
        if (($GLOBALS['access']['main_admin'])||($GLOBALS['settings']['dev_mode'])||($GLOBALS['settings']['show_sql_errors'])) {
            //echo $this->message($msg,'ERROR','alert-error');
            echo $msg;
        } else {
            $email=$GLOBALS['settings']['system_email'];
            $mail_text=$this->message($msg, 'ERROR', 'alert-error');
            $mail_text.="<hr>";
            $mail_text.=$this->pre_display($_GET, 'GET').'<hr>';
            $mail_text.=$this->pre_display($_POST, 'POST').'<hr>';
            $mail_text.=$this->pre_display($_REQUEST, 'REQUEST').'<hr>';

            $mail=$this->mail2admin('SQL error by '.$GLOBALS['username'].', APP:'.$GLOBALS['app_name'].', IP:'.$GLOBALS['ip'], $mail_text);

            echo $this->message('There was an error in SQL statement<br>Contact system administrator', 'ERROR', 'alert-error');
        }
         exit;
    }

    public function mail2admin($subject, $msg)
    {
        $subject=$GLOBALS['app_name'].": $subject";
        $email=$GLOBALS['settings']['admin_mail'];
        if ($email=='') {
            $email=$GLOBALS['settings']['system_email'];
        }
        if ($email=='') {
            $email='it@example.com';
        }
        $mail_text=$msg;
        $mail_text.="<hr>";
        $mail_text.="<b>Time:</b>".date('Y-m-d H:i:s',time()).'<hr>';
        $mail_text.="<b>APP:</b>".$GLOBALS['app_name'].'<hr>';
        $mail_text.="<b>IP:</b>".$GLOBALS['ip'].' -> '.$_SERVER[SERVER_ADDR].'<hr>';
        $mail_text.="<b>User:</b>".$GLOBALS['username'].'<hr>';

        $mail_text.=$this->pre_display($_GET, 'GET').'<hr>';
        $mail_text.=$this->pre_display($_POST, 'POST').'<hr>';
        $mail_text.=$this->pre_display($_REQUEST, 'REQUEST').'<hr>';

        $mail=$this->sendmail_html($email, $email, $subject, $mail_text);
    }

    public function sendmail_html($to, $from, $subject, $message)
    {
        if ($from=='') {
            // $from_user=$this->data->get_row('users',$GLOBALS[uid]);
            // $from_username="$from_user[firstname] $from_user[surname]";
            // $from=$from_user[email];
            // $from_username="IS";
            // $from="info@example.com";
        }
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        $headers  .= "From: $from\r\n";
            //options to send to cc+bcc
            //$headers .= "Cc: [email]maa@p-i-s.cXom[/email]\r\n";
            //$headers .= "Bcc: [email]email@maaking.cXom[/email]\r\n";
        $color="#DCEEFC";
        if ($this->utils->contains('sql', strtolower($subject))) {
            $color="#FF8585";
        }
        if ($this->utils->contains('error', strtolower($subject))) {
            $color="#FF8585";
        }
        $message = "<html>
            <body bgcolor=\"$color\">
            <h3>$subject</h3>
            <p>
            $message
            </p>
            </body>
            </html>";
        //echo $message;
        if ($GLOBALS['settings']['no_mail']!=1) {
            mail($to, $subject, $message, $headers);
        }
        //DB_log("SENT MAIL: $to, SUBJ:$subject, MESG:$message");
        return true;
    }

    // function SQLite3_error($sql = '')
    // {
    //     $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql);
    // }

    // function sql_display($sql = 'select 1', $title = '')
    // {

    //     $result = $this->sql->query($sql) or $this->SQLite3_error($sql);
    //     $result->fetchArray(SQLITE3_NUM);
    //     $fieldnames = [];
    //     $fieldtypes = [];
    //     for ($colnum=0; $colnum<$result->numColumns(); $colnum++) {
    //         $fieldnames[] = $result->columnName($colnum);
    //         $fieldtypes[] = $result->columnType($colnum);
    //     }
    //     $fields=$fieldnames;
    //     array_unshift($fields, "#");
    //     $tbl=$this->tablehead('', '', '', '', $fields);
    //     $result->reset();
    //     while ($row = $result->fetchArray(SQLITE3_NUM)) {
    //         $i++;
    //         $tbl.= "<tr class='$class'>";
    //         $tbl.= "<td>$i</td>";
    //         $j=0;
    //         foreach ($fieldnames as $fieldname) {
    //             $class=($fieldtypes[$j]==2)?"n":"";
    //             $tbl.= "<td class='$class'>$row[$j]</td>";
    //             $j++;
    //         }

    //         $tbl.= "</tr>";
    //     }
    //     $tbl.="</table>";

    //     if ($title!='') {
    //         $out.=$this->tag($title, 'foldered');
    //     }
    //     $result->finalize();
    //     return $out.$tbl;
    // }

    function dropzoneJS($formdata = '', $text = 'Drop files here',$inline=0)
    {
        $UUID = md5(uniqid(rand(), true));
        $related_data=json_decode($formdata, true);
        //echo $this->pre_display($related_data,'related_data'.$UUID); //exit;
        foreach ($related_data as $key => $value) {
            $hidden.=$this->form_hidden($key, $value);
        }
        if($inline>0){
            $style='style="width:100%; margin:0"';
            $text="";
        }

        $out='
            <span id="up-ack"></span>
            <form id="file-up_'.$UUID.'" class="dropzone" '.$style.'>
            '.$hidden.'
            </form>

            <!-- here you display files -->
            <span id="show-files"></span>

            <div class="col-md-2">
            <!-- Right Side-->
            </div>
            </div>
            </div><!-- jQuery -->

            <script src="'.ASSETS_URI.'/assets/js/dropzone.js"></script>

            <script language="JavaScript">
            $(document).ready(function () {
                 //prevent error: "Error: Dropzone already attached."
                  Dropzone.autoDiscover = false;
                  $("#file-up_'.$UUID.'").dropzone({
                    url: "?act=save&what=dropzone&plain=1",
                    addRemoveLinks: true,
                    parallelUploads: 10,
                    uploadMultiple: false,
                    dictDefaultMessage:"'.$text.'",
                    maxFilesize: 256, // MB // you can add more or less
                    //acceptedFiles: ".jpeg, .jpg, .jpe, .bmp, .png, .gif, .ico, .tiff, .tif, .svg, .svgz,.doc,.docx,.txt, .pdf,.rtf,.xlsx,.xls,.xlsb,.csv, .ppt,.zip,.zipx,.tar,.gz,.z,.rar,.eml,.xml", // files you accepting

                    success: function (file, response) {
                        var imgName = response;
                        file.previewElement.classList.add("dz-success");
                        $(\'#up-ack\').html(response); // get the file upload responses
                    },

                    error: function (file, response) {
                        file.previewElement.classList.add("dz-error");
                         $(\'#up-ack\').css(\'color\',\'red\').html(response);
                    }
                });
            });
            </script>
        ';
        return $out;
    }
    function QRscan()
    {
        $out='

            <style type="text/css">

            img{
                border:0;
            }
            #main{
                margin: 15px auto;
                background:white;
                overflow: auto;
                width: 100%;
            }
            #header{
                background:white;
                margin-bottom:15px;
            }
            #mainbody{
                background: white;
                width:100%;
                display:none;
            }
            #footer{
                background:white;
            }
            #v{
                width:320px;
                height:240px;
            }
            #qr-canvas{
                display:none;
            }
            #qrfile{
                width:320px;
                height:240px;
            }
            #mp1{
                text-align:center;
                font-size:35px;
            }
            #imghelp{
                position:relative;
                left:0px;
                top:-160px;
                z-index:100;
                font:18px arial,sans-serif;
                background:#f0f0f0;
                margin-left:35px;
                margin-right:35px;
                padding-top:10px;
                padding-bottom:10px;
                border-radius:20px;
            }
            .selector{
                margin:0;
                padding:0;
                cursor:pointer;
                margin-bottom:-5px;
            }
            #outdiv
            {
                width:320px;
                height:240px;
                border: solid;
                border-width: 3px 3px 3px 3px;
            }
            #result{
                border: solid;
                border-width: 1px 1px 1px 1px;
                padding:20px;
                width:70%;
            }

            ul{
                margin-bottom:0;
                margin-right:40px;
            }
            li{
                display:inline;
                padding-right: 0.5em;
                padding-left: 0.5em;
                font-weight: bold;
                border-right: 1px solid #333333;
            }
            li a{
                text-decoration: none;
                color: black;
            }

            #footer a{
                color: black;
            }
            .tsel{
                padding:0;
            }

            </style>

            <script type="text/javascript" src="'.ASSETS_URI.'/assets/js/llqrcode.js"></script>
            <script type="text/javascript" src="'.ASSETS_URI.'/assets/js/webqr.js"></script>


        <div id="main">

        <div id="mainbody">
        <table class="tsel" border="0" width="100%">
        <tr>
        <td valign="top" align="center" width="50%">
        <table class="tsel" border="0">
        <tr>
        <td><img class="selector" id="webcamimg" src="assets/img/vid.png" onclick="setwebcam()" align="left" /></td>
        <td><img class="selector" id="qrimg" src="assets/img/cam.png" onclick="setimg()" align="right"/></td></tr>
        <tr><td colspan="2" align="center">
        <div id="outdiv">
        </div></td></tr>
        </table>
        </td>
        </tr>

        <tr><td colspan="3" align="center">
        <div id="result"></div>
        </td></tr>
        </table>
        <!-- webqr_2016 -->

        </div>&nbsp;

        </div>
        <canvas id="qr-canvas" width="800" height="600"></canvas>
        <script type="text/javascript">load();</script>

        ';
        return $out;
    }
    function cameraJS($url = '?act=save&what=webcam&plain=1', $title = '', $settings = array('size'=>'640x480','dest'=>'640x480','crop'=>'640x480','quality'=>'90','flip'=>'0'))
    {
        $size=explode('x', $settings['size']);
        $dest=explode('x', $settings['dest']);
        $crop=explode('x', $settings['crop']);

        $size_js="width: $size[0], height: $size[1],";
        $dest_js="dest_width: $dest[0], dest_height: $dest[1],";
        $crop_js="crop_width: $crop[0], crop_height: $crop[1],";

        $quality_js="jpeg_quality: $settings[quality],";
        if ($settings['flip']==1) {
            $flip_js="flip_horiz: true";
        }

        $out='
            <h1>'.$title.'</h1>

            <div id="my_photo_booth">
                <div id="my_camera"></div>

                <!-- First, include the Webcam.js JavaScript Library -->
                <script type="text/javascript" src="'.ASSETS_URI.'/assets/js/webcam.js"></script>

                <!-- Configure a few settings and attach camera -->
                <script language="JavaScript">
                    Webcam.set({
                        // live preview size
                        '.$size_js.'
                        // device capture size
                        '.$dest_js.'

                        // final cropped size
                        '.$crop_js.'

                        // format and quality
                        image_format: \'jpeg\',
                        '.$quality_js.'

                        // flip horizontal (mirror mode)
                        '.$flip_js.'
                    });



                    Webcam.attach( \'#my_camera\' );
                </script>

                <!-- A button for taking snaps -->
                <form>
                    <div id="pre_take_buttons">
                        <!-- This button is shown before the user takes a snapshot -->
                        <input type=button value="Take Snapshot" onClick="preview_snapshot()">
                    </div>
                    <div id="post_take_buttons" style="display:none">
                        <!-- These buttons are shown after a snapshot is taken -->
                        <input type=button value="&lt; Take Another" onClick="cancel_preview()">
                        <input type=button value="Save Photo &gt;" onClick="save_photo()" style="font-weight:bold;">
                    </div>
                </form>
            </div>

            <div id="results" style="display:none">
                <!-- Your captured image will appear here... -->
            </div>

            <!-- Code to handle taking the snapshot and displaying it locally -->
            <script language="JavaScript">
                // preload shutter audio clip
                var shutter = new Audio();
                shutter.autoplay = false;
                shutter.src = navigator.userAgent.match(/Firefox/) ? \'shutter.ogg\' : \'shutter.mp3\';

                function preview_snapshot() {
                    // play sound effect
                    try { shutter.currentTime = 0; } catch(e) {;} // fails in IE
                    shutter.play();

                    // freeze camera so user can preview current frame
                    Webcam.freeze();

                    // swap button sets
                    document.getElementById(\'pre_take_buttons\').style.display = \'none\';
                    document.getElementById(\'post_take_buttons\').style.display = \'\';
                }

                function cancel_preview() {
                    // cancel preview freeze and return to live camera view
                    Webcam.unfreeze();

                    // swap buttons back to first set
                    document.getElementById(\'pre_take_buttons\').style.display = \'\';
                    document.getElementById(\'post_take_buttons\').style.display = \'none\';
                }

                function save_photo() {
                    // actually snap photo (from preview freeze) and display it


                    Webcam.snap(function(data_uri) {

                        Webcam.on( \'uploadProgress\', function(progress) {
                                    // Upload in progress
                                    // \'progress\' will be between 0.0 and 1.0
                                    document.getElementById(\'results\').innerHTML = progress;
                                } );

                                Webcam.on( \'uploadComplete\', function(code, text) {
                                    // Upload complete!
                                    // \'code\' will be the HTTP response code from the server, e.g. 200
                                    // \'text\' will be the raw response content
                                    document.getElementById(\'results\').innerHTML = text;

                                } );

                                Webcam.upload( data_uri, \''.$url.'\' );

                        // display results in page
                        //document.getElementById(\'results\').innerHTML =
                        //  \'<h2>Here is your large, cropped image:</h2>\' +
                        //  \'<img src="\'+data_uri+\'"/><br/></br>\' +
                        //  \'<a href="\'+data_uri+\'" target="_blank">Open image in new window...(\'+data_uri+\')</a>\';

                        Webcam.unfreeze();
                        // shut down camera, stop capturing
                        Webcam.reset();
                        Webcam.attach( \'#my_camera\' );

                        // show results, hide photo booth
                        document.getElementById(\'results\').style.display = \'\';
                        //document.getElementById(\'my_photo_booth\').style.display = \'none\';
                    } );
                }
            </script>';
        return $out;
    }


    function HT_pager2($recs = 0, $qry = '')
    {
        return $this->HT_pager($recs, $qry);
    }
    function HT_pager($recs = 0, $qry = '')
    {
        global $limit;
        //$nav.="$recs,$qry<br>";
        if ($recs>$limit) {
            $page = $this->readRQn('page');
        //$nav.= "Recs:$recs,Q:$qry,L:$limit,Page:$page<br>";
            $currpage=$page;
            $offset = $limit*$page;
            $pages=floor($recs/$limit);
            for ($i=0; $i<=10; $i++) {
                $navpage[$i]=$page-5+$i;
            }
            $formdata="";
            foreach ($_POST as $key => $value) {
                $formdata="$formdata&$key=$value";
            }
            $sqry=$_SERVER["QUERY_STRING"].$formdata;
            $sqry=explode("&page=", $sqry);
            $sqry=$sqry[0];
            $qry=explode("&page=", $qry);
            $qry=$qry[0];
        //$navpage[5]="[".$navpage[5]."]";
            $nav.="<div class='pagination pagination-condensed'>
        <ul>
          <li><a href='?$qry&page=0' TITLE='First Page' class='t'>First</a></li>
          <li><a href='?$qry&page=$navpage[4]' TITLE='Previous Page' class='t'>&larr;</a></li>";
            for ($i=0; $i<=10; $i++) {
                if (($navpage[$i]>=0)&&($navpage[$i]<=$pages)) {
                    if ($i==5) {
                        $nav="$nav<li class='active'><a href='?$qry&page=$navpage[$i]' TITLE='Page ".$navpage[$i]."'>".$navpage[$i]."</a></li>";
                    } else {
                        $nav.="<li><a href='?$qry&page=$navpage[$i]' TITLE='Page ".$navpage[$i]."'>".$navpage[$i]."</a></li>";
                    }
                }
            }

            $nav.="<li><a href='?$qry&page=$navpage[6]' TITLE='Next Page' class='t'>&rarr;</a></li>
          <li><a href='?$qry&page=$pages' TITLE='Last Page' class='t'>Last (".($pages).")</a></li>
          <li><a href='?$qry&nopager=1' TITLE='NoPager' class='t'>All ($recs)</a></li>
          </ul></div>";
        }
        return $nav;
    }
    function HT_ajaxpager($recs = 0, $qry = '', $destination = '')
    {
        global $limit;
        //$destination='Transactions_All.';
        if ($recs>$limit) {
        //$destination="Client Requests.";
            $page =$this->readRQ("page")*1;
            $currpage=$page;
            $offset = $limit*$page;
            $pages=floor($recs/$limit);
            for ($i=0; $i<=10; $i++) {
                $navpage[$i]=$page-5+$i;
            }
            $formdata="";
            foreach ($_POST as $key => $value) {
                $formdata="$formdata&$key=$value";
            }
            $sqry=$_SERVER["QUERY_STRING"].$formdata;
            $sqry=explode("&page=", $sqry);
            $sqry=$sqry[0];
            $qry=explode("&page=", $qry);
            $qry=$qry[0];
            $allqry=$this->utils->replaceValue($qry, 'bbf', 0);
        //$navpage[5]="[".$navpage[5]."]";
        //$nav="<span onclick='ajaxFunction(\"Client Requests.act_\",\"?act=show&what=partners&page=0&plain=1\");' onmouseover=\"this.style.cursor='pointer';\" TITLE='First Page' class='t'>Test</span>";
            $nav.="<div class='pagination pagination-condensed'>
            <ul>
            <li onclick='ajaxFunction(\"$destination\",\"?$allqry&page=0&plain=1\");' onmouseover=\"this.style.cursor='pointer';\" TITLE='First Page' class='t'><a>First</a></li>
            ";
            for ($i=0; $i<=10; $i++) {
                if (($navpage[$i]>=0)&&($navpage[$i]<=$pages)) {
                    if ($i==5) {
                        $nav="$nav
                <li class='active' onclick='ajaxFunction(\"$destination\",\"?$qry&page=$navpage[$i]&plain=1\");' onmouseover=\"this.style.cursor='pointer';\" TITLE='Page ".$navpage[$i]."' class='t'><a>".$navpage[$i]."</a> </li>";
                    } else {
                        $nav="$nav
                <li onclick='ajaxFunction(\"$destination\",\"?$qry&page=$navpage[$i]&plain=1\");' onmouseover=\"this.style.cursor='pointer';\" TITLE='Page ".$navpage[$i]."' class='t'><a>".$navpage[$i]."</a> </li>";
                    }
                }
            }

            $nav="$nav
            <li onclick='ajaxFunction(\"$destination\",\"?$qry&page=$pages&plain=1\");' onmouseover=\"this.style.cursor='pointer';\" TITLE='Last Page' class='t'><a>Last (".($pages).")</a> </li>
            <li onclick='ajaxFunction(\"$destination\",\"?$allqry&nopager=1&plain=1\");' onmouseover=\"this.style.cursor='pointer';\" TITLE='No Pages' class='t'><a>All (".($recs).")</a> </li>
             </ul>
            </div></div>";
        }
        return $nav;
    }


    function edit_rec($what = '', $id, $act = 'ved', $text = '', $warn = '')
    {
        global $access;
        $v=($this->utils->contains('v', $act));
        $e=($this->utils->contains('e', $act));
        $d=($this->utils->contains('d', $act));

        //echo "$e";

        if (($access['view_'.$what])&&($v)) {
            $view= "<a href='".$this->link(array('act'=>'details','what'=>$what,'id'=>$id))."'><i class='icon-eye-open icon-white withpointer edit-icon'></i></a>";
        }
        if (($access['edit_'.$what])&&($e)) {
            $edit= "<a href='".$this->link(array('act'=>'edit','what'=>$what,'id'=>$id))."'><i class='icon-pencil icon-white withpointer edit-icon'></i></a>";
        }
        if (($access['edit_'.$what])&&($d)) {
            $delete= "<i class='icon-trash icon-white withpointer edit-icon' onclick=\"confirmation('".$this->link(array('csrf'=>$GLOBALS[csrf],'act'=>'delete','what'=>$what,'id'=>$id))."','$warn')\"></i>";
        }
        $responce= "<td></td>\n";

        $out="<span hidden class='row-editable-icons' id='icons:${what}_${id}'>&nbsp;$view $edit $delete&nbsp;</span>";
        $out="<td class='row-editable' id='place:${what}_${id}'>$out $text</td>";
        return $out;
    }

    function manage_rec($what = '', $id=0, $act = 'svedai', $text = '', $warn = '', $query_v=[], $query_a=[], $query_i=[])
    {
        global $access;
        $s=($this->utils->contains('s', $act));
        $v=($this->utils->contains('v', $act));
        $e=($this->utils->contains('e', $act));
        $d=($this->utils->contains('d', $act));
        $a=($this->utils->contains('a', $act));
        $i=($this->utils->contains('i', $act));

        //echo "$e";
        //<td id='$what:$row[id]' class='cart-selectable' reference='$what'>$row[id]</td>
        if (($access['view_'.$what])&&($s)) {
            $select= "<span id='$what:$id' class='cart-selectable' reference='$what'><i class='icon-ok-circle icon-white withpointer edit-icon'></i></span>";
        }
        if (($access['view_'.$what])&&($v)) {
            $view= "<a href='".$this->link(['act'=>'details','what'=>$what,'id'=>$id]+$query_v)."'><i class='icon-eye-open icon-white withpointer edit-icon'></i></a>";
        }
        if (($access['edit_'.$what])&&($e)) {
            $edit= "<a href='".$this->link(['act'=>'edit','what'=>$what,'id'=>$id])."'><i class='icon-pencil icon-white withpointer edit-icon'></i></a>";
        }
        if (($access['edit_'.$what])&&($a)) {
            $add= "<a href='".$this->link(['act'=>'add','what'=>$what]+$query_a)."'><i class='icon-plus-sign icon-white withpointer edit-icon'></i></a>";
        }
        if (($access['edit_'.$what])&&($i)) {
            $insert= "<a href='".$this->link(['act'=>'add','what'=>$what]+$query_i)."'><i class='icon-plus icon-white withpointer edit-icon'></i></a>";
        }
        if (($access['edit_'.$what])&&($d)) {
            $delete= "<i class='icon-trash icon-white withpointer edit-icon' onclick=\"confirmation('".$this->link(['csrf'=>$GLOBALS[csrf],'act'=>'delete','what'=>$what,'id'=>$id])."','$warn')\"></i>";
        }
        $responce= "<td></td>\n";

        $out="<span hidden class='row-editable-icons' id='icons:${what}_${id}'>&nbsp;$select $view $edit $add $insert $delete&nbsp;</span>";
        $out="<span class='row-editable' id='place:${what}_${id}'>$out $text </span>";
        return $out;
    }

    function HT_editicons($table = '', $id = 0, $print = '')
    {
        return $this->HT_editicons2($table, $id, $print);
    }
    function HT_editicons2($table = '', $id = 0, $print = '')
    {

        if (($GLOBALS[edit_icons]=='normal')||($GLOBALS[edit_icons]=='')) {
            return $this->HT_editiconsShow($table, $id, $print);
        }
        if ($GLOBALS[edit_icons]=='hidden') {
            return $this->HT_editiconsHide($table, $id, $print);
        }
        if ($GLOBALS[edit_icons]=='normal2') {
            return $this->HT_editiconsShowE($table, $id, $print);
        }
        if ($GLOBALS[edit_icons]=='hidden2') {
            return $this->HT_editiconsHideE($table, $id, $print);
        }
    }
    function HT_editiconsHideE($table = '', $id = 0, $print = '')
    {
        global $access;
        $text="Are you sure you whant to delete record ID:$id from $table?";
        $responce="";
        $h=16;
        $w=16;
        $access['view_'.$table]=true;
        $access['edit_'.$table]=true;
              $responce.= "<td>
                <div class='dropdown2 dropdown-toggle' data-toggle='dropdown2'><img src='".ASSETS_URI."/assets/img/custom/empty.png' width='1' height='1'>
                          <div class='dropdown-menu2'>";
            //if ($access['view_'.$table]){ $responce.= "<a href='".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."'><i class='icon-eye-open withpointer' onclick=\"goto('".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."')\"></i></a>";}
            //if ($access['edit_'.$table]){ $responce.= "<a href='".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."'><i class='icon-pencil withpointer' onclick=\"goto('".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."')\"></i></a>";}

        if ($access['view_'.$table]) {
            $responce.= "<a href='".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."'><i class='icon-eye-open withpointer' onMouseOver=\"this.className='icon-eye-open icon-white withpointer black'\" onMouseOut=\"this.className='icon-eye-open withpointer'\"></i></a>";
        }
        if ($access['edit_'.$table]) {
            $responce.= "<a href='".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."'><i class='icon-pencil withpointer' onMouseOver=\"this.className='icon-pencil icon-white withpointer black'\" onMouseOut=\"this.className='icon-pencil withpointer'\"></i></a>";
        }
        if ($access['edit_'.$table]) {
            $responce.= "<i class='icon-trash withpointer' onclick=\"confirmation('".$this->link(array('csrf'=>$GLOBALS[csrf],'act'=>'delete','what'=>$table,'id'=>$id))."','$text')\" onMouseOver=\"this.className='icon-trash icon-white withpointer black'\" onMouseOut=\"this.className='icon-trash withpointer'\"></i>";
        }
            $responce.= "</div>
            </div></td>\n";
            return $responce;
    }
    function HT_editiconsHide($table = '', $id = 0, $print = '')
    {
        global $access;
        $text="Are you sure you whant to delete record ID:$id from $table?";
        $responce="";
        $h=16;
        $w=16;
        $access['view_'.$table]=true;
        $access['edit_'.$table]=true;
              $responce.= "<td>
                <div class='dropdown2 dropdown-toggle' data-toggle='dropdown2'><img src='".ASSETS_URI."/assets/img/custom/empty.png' width='1' height='1'>
                          <div class='dropdown-menu2'>";
            //if ($access['view_'.$table]){ $responce.= "<a href='".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."'><i class='icon-eye-open withpointer' onclick=\"goto('".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."')\"></i></a>";}
            //if ($access['edit_'.$table]){ $responce.= "<a href='".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."'><i class='icon-pencil withpointer' onclick=\"goto('".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."')\"></i></a>";}

        if ($access['view_'.$table]) {
            $responce.= "<a href='".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."'><i class='icon-eye-open withpointer'></i></a>";
        }
        if ($access['edit_'.$table]) {
            $responce.= "<a href='".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."'><i class='icon-pencil withpointer'></i></a>";
        }
        if ($access['edit_'.$table]) {
            $responce.= "<i class='icon-trash withpointer' onclick=\"confirmation('".$this->link(array('csrf'=>$GLOBALS[csrf],'act'=>'delete','what'=>$table,'id'=>$id))."','$text')\"></i>";
        }
            $responce.= "</div>
            </div></td>\n";
            return $responce;
    }
    function HT_editiconsShowE($table = '', $id = 0, $print = '')
    {
        global $access;
        $text="Are you sure you whant to delete record ID:$id from $table?";
        $responce="";
        $h=16;
        $w=16;
        $access['view_'.$table]=true;
        $access['edit_'.$table]=true;
              $responce.= "<td>";
            //if ($access['view_'.$table]){ $responce.= "<a href='".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."'><i class='icon-eye-open withpointer' onclick=\"goto('".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."')\"></i></a>";}
            //if ($access['edit_'.$table]){ $responce.= "<a href='".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."'><i class='icon-pencil withpointer' onclick=\"goto('".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."')\"></i></a>";}

        if ($access['view_'.$table]) {
            $responce.= "<a href='".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."'><i class='icon-eye-open withpointer' onMouseOver=\"this.className='icon-eye-open icon-white withpointer black'\" onMouseOut=\"this.className='icon-eye-open withpointer'\"></i></a>";
        }
        if ($access['edit_'.$table]) {
            $responce.= "<a href='".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."'><i class='icon-pencil withpointer' onMouseOver=\"this.className='icon-pencil icon-white withpointer black'\" onMouseOut=\"this.className='icon-pencil withpointer'\"></i></a>";
        }
        if ($access['edit_'.$table]) {
            $responce.= "<i class='icon-trash withpointer' onclick=\"confirmation('".$this->link(array('csrf'=>$GLOBALS[csrf],'act'=>'delete','what'=>$table,'id'=>$id))."','$text')\" onMouseOver=\"this.className='icon-trash icon-white withpointer black'\" onMouseOut=\"this.className='icon-trash withpointer'\"></i>";
        }
            $responce.= "</td>\n";
            return $responce;
    }

    function HT_editiconsShow($table = '', $id = 0, $print = '')
    {
        global $access;
        $text="Are you sure you whant to delete record ID:$id from $table?";
        $h=16;
        $w=16;
        $access['view_'.$table]=true;
        $access['edit_'.$table]=true;
        $nodedit=$this->readRQn('noedit');
        $nodelete=$this->readRQn('nodelete');
        $noview=$this->readRQn('noview');
        //if ($access['view_'.$table]){ $responce.= "<a href='".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."'><i class='icon-eye-open withpointer' onclick=\"goto('".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."')\"></i></a>";}
        //if ($access['edit_'.$table]){ $responce.= "<a href='".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."'><i class='icon-pencil withpointer' onclick=\"goto('".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."')\"></i></a>";}
        if (($access['view_'.$table])&&(!$noview)) {
            $view= "<a href='".$this->link(array('act'=>'details','what'=>$table,'id'=>$id))."'><i class='icon-eye-open withpointer'></i></a>";
        }
        if (($access['edit_'.$table])&&(!$nodedit)) {
            $edit= "<a href='".$this->link(array('act'=>'edit','what'=>$table,'id'=>$id))."'><i class='icon-pencil withpointer'></i></a>";
        }
        if (($access['edit_'.$table])&&(!$nodelete)) {
            $delete= "<i class='icon-trash withpointer' onclick=\"confirmation('".$this->link(array('csrf'=>$GLOBALS[csrf],'act'=>'delete','what'=>$table,'id'=>$id))."','$text')\"></i>";
        }
        $responce= "<td>$view $edit $delete</td>\n";
        return $responce;
    }
    function filesize($size)
    {
        if ($size < 1000) {
            return sprintf('%s B', $size);
        } elseif (($size / 1024) < 1000) {
            return sprintf('%s KB', round(($size / 1024), 2));
        } elseif (($size / 1024 / 1024) < 1000) {
            return sprintf('%s MB', round(($size / 1024 / 1024), 2));
        } elseif (($size / 1024 / 1024 / 1024) < 1000) {
            return sprintf('%s GB', round(($size / 1024 / 1024 / 1024), 2));
        } else {
            return sprintf('%s TB', round(($size / 1024 / 1024 / 1024 / 1024), 2));
        }
    }
    function show_folder($path,$where='tmp'){
        if($path==''||$path=='/'||$path=='\\')
            return "Wrong path '$path'";
        //echo "$path<br>";
        $objects = is_readable($path) ? scandir($path) : array();
        $folders = array();
        $files = array();

        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file == '.' || $file == '..' && in_array($file, $GLOBALS['exclude_items'])) {
                    continue;
                }
                if (!FM_SHOW_HIDDEN && substr($file, 0, 1) === '.') {
                    continue;
                }
                $new_path = $path . '/' . $file;
                if (@is_file($new_path) && !in_array($file, $GLOBALS['exclude_items'])) {
                    $files[] = $file;
                } elseif (@is_dir($new_path) && $file != '.' && $file != '..' && !in_array($file, $GLOBALS['exclude_items'])) {
                    $folders[] = $file;
                }
            }
        }
        if (!empty($files)) {
            natcasesort($files);
        }
        if (!empty($folders)) {
            natcasesort($folders);
        }
        $fields=['#','t','Name','size',' '];
        $out=$this->tablehead('','', $order, 'no_addbutton', $fields,$sort);

        $num_files = count($files);
        $num_folders = count($folders);
        $all_files_size = 0;

        foreach ($folders as $f) {
            $i++;
            $is_link = is_link($path . '/' . $f);
            $img = $is_link ? 'icon-link_folder' : 'fa fa-folder-o';
            $modif = date(FM_DATETIME_FORMAT, filemtime($path . '/' . $f));
            $perms = substr(decoct(fileperms($path . '/' . $f)), -4);
            if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                $owner = posix_getpwuid(fileowner($path . '/' . $f));
                $group = posix_getgrgid(filegroup($path . '/' . $f));
            } else {
                $owner = array('name' => '?');
                $group = array('name' => '?');
            }
            $out.= "<tr class='$class bold'>";
            $out.= "<td>$i</td>";
            //$out.= $this->edit_rec($what,$row[id],'ved',$i);
            //$out.= "<td id='$what:$row[id]' class='cart-selectable' reference='$what'>$row[id]</td>";
            //$out.= "<td onMouseover=\"showhint('$row[descr]', this, event, '400px');\">$row[name]</td>";
            $out.= "<td><i class='icon-folder-open'></i></td>";
            $out.= "<td>$f</td>";

            $out.= "<td>$filesize</td>";
            $link= "<a href='?act=details&what=file_content&where=$where&plain=1&filename=$f' onMouseover=\"showhint('$row[name]', this, event, '200px');\"><i class='icon-eye-open'></i></a>";
            $out.= "<td> </td>";
            //$out.=$this->HT_editicons($what, $row[id]);
            $out.= "</tr>";
        }
        foreach ($files as $f) {
            $i++;
            $file_extension=$this->utils->file_extension($f);
            $is_link = is_link($path . '/' . $f);
            //$img = $is_link ? 'fa fa-file-text-o' : fm_get_file_icon_class($path . '/' . $f);
            $modif = date(FM_DATETIME_FORMAT, filemtime($path . '/' . $f));
            //$filesize_raw = fm_get_size($path . '/' . $f);
            $filesize_raw = filesize($path . '/' . $f);
            $filesize = $this->filesize($filesize_raw);
            $filelink = '?p=' . urlencode(FM_PATH) . '&amp;view=' . urlencode($f);
            $all_files_size += $filesize_raw;
            $perms = substr(decoct(fileperms($path . '/' . $f)), -4);
            if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                $owner = posix_getpwuid(fileowner($path . '/' . $f));
                $group = posix_getgrgid(filegroup($path . '/' . $f));
            } else {
                $owner = array('name' => '?');
                $group = array('name' => '?');
            }
            $out.= "<tr class='$class'>";
            $out.= "<td>$i</td>";
            $out.= "<td><i class='icon-file'></i></td>";
            //$out.= $this->edit_rec($what,$row[id],'ved',$i);
            //$out.= "<td id='$what:$row[id]' class='cart-selectable' reference='$what'>$row[id]</td>";
            //$out.= "<td onMouseover=\"showhint('$row[descr]', this, event, '400px');\">$row[name]</td>";
            $out.= "<td>$f</td>";
            $out.= "<td class='n'>$filesize</td>";
            $edit= "";
            $view= "<a href='?act=details&what=file_content&where=$where&plain=1&filename=$f' onMouseover=\"showhint('View', this, event, '50');\"><i class='icon-eye-open'></i></a>";
            $download= "<a href='?act=details&what=file_content&where=$where&plain=1&filename=$f' onMouseover=\"showhint('Download', this, event, '50');\"><i class='icon-download'></i></a>";
            if($file_extension=='json'){
                $edit= "<a href='?act=form&what=file_json&where=$where&plain=&filename=$f' onMouseover=\"showhint('Edit', this, event, '50');\"><i class='icon-edit'></i></a>";
            }
            if($file_extension=='txt'){
                $edit= "<a href='?act=form&what=edit_file_content&where=$where&plain=1&filename=$f' onMouseover=\"showhint('Edit', this, event, '50');\"><i class='icon-edit'></i></a>";
            }

            $send= "<a href='?act=details&what=file_content&where=$where&plain=1&filename=$f' onMouseover=\"showhint('Send', this, event, '50');\"><i class='icon-envelope'></i></a>";
            $send=$this->confirm_with_comment("<i class='icon-envelope'></i>", "?act=details&what=file_content&where=$where&plain=1&filename=$f", 'nano', 'Enter email address');
            $out.= "<td>$view $send $download $edit</td>";
            //$out.=$this->HT_editicons($what, $row[id]);
            $out.= "</tr>";
        }
        $out.=$this->tablefoot($i, $totals, $totalrecs);
        return $out;
    }
}
