<?php
namespace Rozdol\Utils;

use Rozdol\Dates\Dates;

class Utils
{
    private static $hInstance;

    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new Utils();
        }
        return self::$hInstance;
    }

    public function __construct()
    {
        $this->dates = Dates::getInstance();
        //if($GLOBALS['SQLite3']!='')$this->sql = new \SQLite3(':memory:') or die('Unable to open database');
        if (extension_loaded('SQLite3')) {
            $this->sql = new \SQLite3(':memory:') or die('Unable to open SQLite3 database');
        }
    }

    public function contains($str, $instr)
    {
        $result=0;
        $pos = strpos($instr, $str);
        if ($pos === false) {
            $result=0;
        } else {
            $result=1;
        }
        return $result;
    }

    public function tbl2csv($out2)
    {
        if ($GLOBALS['settings']['use_tbl2csv']>0) {
            $csv.="\n";
            $file=FW_DIR.'/classes/simple_html_dom.php';
            if (!file_exists($file)) {
                die("No file $file");
            }
            include_once($file);
            //$simple_html = new simple_html();
            //$html = $simple_html->str_get_html($out2);


            $html = new \simple_html_dom(null, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT);
            if (empty($str) || strlen($str) > MAX_FILE_SIZE) {
                $html->clear();
                return false;
            }
            $html->load($str, $lowercase, $stripRN);

            foreach ($html->find('tr') as $element) {
                $td = array();
                foreach ($element->find('th') as $row) {
                    $td [] = $row->plaintext;
                }
                $csv.=implode("\t", $td);

                $td = array();
                foreach ($element->find('td') as $row) {
                    $td [] = $row->plaintext;
                }

                $csv.=implode("\t", $td);
                $csv.="\n";
            }
            echo $csv;
            exit;
            return $csv;
        } else {
            return "Table2CSV turned off.";
        }
    }
    public function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
          //check ip from share internet
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
          //to check ip is pass from proxy
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    public function findnumber($txt)
    {
        $sum=$txt;
        $sum=str_ireplace(",", "", $sum);
        $sum=str_ireplace(" ", "", $sum);
        $sum=str_ireplace("\t", "", $sum);
        $sum=str_ireplace(" ", "", $sum);
        preg_match_all('(\d+(?:.\d+)?)', $sum, $matches);
        preg_match_all('(\d+(?:M\d+)?)', $txt, $matches2);
    //var_dump($matches2);
    //echo $this->pre_display($matches2);
        $sum=$matches[0][0]*1;
        return $sum;
    }
    public function cleannumber($sum, $dec = '')
    {


        $multiplier=1;
        if ($dec!='') {
            $sum=str_ireplace(",", ".", $sum);
        } else {
            $sum=str_ireplace(",", "", $sum);
        }
        $sum=str_ireplace(" ", "", $sum);
        $sum=str_ireplace("\t", "", $sum);
        $sum=str_ireplace(" ", "", $sum);




        $token="CR";
        if (stripos($sum, $token)!== false) {
            $sum=str_ireplace($token, "", $sum);
            $multiplier=1;
        }
        $token="DR";
        if (stripos($sum, $token)!== false) {
            $sum=str_ireplace($token, "", $sum);
            $multiplier=-1;
        }


        $token="-";
        if (stripos($sum, $token)!== false) {
            $sum=str_ireplace($token, "", $sum);
            $multiplier=-1;
        }


        $sum=(float)filter_var($sum, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        //preg_match_all('!\d+!', $sum, $matches);
        //$sum=$matches[0][0]*1;

        $sum=$sum*$multiplier;
        return $sum;
    }



    /////=======Unrevised below===========
    ///
    ///

    public function escape($array = [])
    {
        foreach ($array as $key => $value) {
            $array[$key]=htmlspecialchars($value, ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5, 'UTF-8');
        }
        return $array;
    }

    public function unescape($array)
    {
        foreach ($array as $key => $value) {
            $array[$key]=htmlspecialchars_decode($value, ENT_QUOTES|ENT_HTML5);
        }
        return $array;
    }

    public function log($data = '')
    {
        $log  = date("d.m.y G:i").' - '.$_SERVER['REMOTE_ADDR'].' - '.$data.PHP_EOL;
        file_put_contents('./log_'.APP_NAME.'_'.date("d.m.y").'.log', $log, FILE_APPEND);
        return true;
    }






    public function F_toarray($array1)
    {
        $res=array();
        foreach ($array1 as $key1 => $value1) {
            foreach ($value1 as $key => $value) {
                array_push($res, $value);
            }
        }
        return $res;
    }
    public function F_toarray_associative($array1)
    {
        $res=array();
        foreach ($array1 as $key1 => $value1) {
            $i=0;
            foreach ($value1 as $key => $value) {
                if ($i==0) {
                    $name=$value;
                } else {
                    $value=$value;
                }
                $i++;
            }
            $items = array("$name" => "$value");
            $this->array_push_associative($res, $items);
        }
        return $res;
    }

    public function F_srttoarray($string)
    {
        $res=array();
        $res=explode(",", $string);
        return $res;
    }
    public function F_tostring($array1 = [], $deletelast = '', $delim = ',')
    {
        $res="";
        foreach ($array1 as $key1 => $value1) {
            foreach ($value1 as $key => $value) {
                $res.="$value$delim";
            }
        }
        $dellen=strlen($delim)*-1;
        if ($deletelast!="") {
            $res=substr($res, 0, $dellen);
        }
        return $res;
    }
    public function F_totext($array1)
    {
        $res="";
        foreach ($array1 as $key1 => $value1) {
             $i=0;
            $s=sizeof($value1);
            foreach ($value1 as $key => $value) {
                $i++;
                if ($i < $s) {
                    $res.=$value."\t";
                }
            }
             //foreach ($value1 as $key => $value) {$res.=$value."\t";}
             $res.=$value."\n";
        }
        return $res;
    }


    public function replaceValue($orgqry, $var, $val)
    {
        $finalquery='';
        $found=0;
        //$finalquery=$orgqry;
        $pairs=explode("&", $orgqry);
        foreach ($pairs as $pair) {
            $tokens=explode("=", $pair);
            //echo "";
            if ($tokens[0]!='') {
                if ($tokens[0]==$var) {
                    $finalquery=$finalquery."&".$tokens[0]."=".$val;
                    $found++;
                } else {
                    $finalquery=$finalquery."&".$tokens[0]."=".$tokens[1];
                }
            }
        }
        if ($found==0) {
            $finalquery=$finalquery."&".$var."=".$val;
        }
        $finalquery=substr($finalquery, 1);
        return $finalquery;
    }
    public function replaceValRQ($var, $val)
    {
        $finalquery='';
        $found=0;
        $orgqry=$_SERVER["QUERY_STRING"];
        $finalquery=$this->replaceValue($orgqry, $var, $val);
        $_SERVER["QUERY_STRING"]=$finalquery;
        return $finalquery;
    }
    public function exportcsv($csv = [], $short = '')
    {
        if ($GLOBALS[settings][no_export]) {
            return;
        }
        if (is_array($csv)) {
            $csv_arr=$csv;
            $csv='';
            foreach ($csv_arr as $row) {
                $rows[]=implode("\t", $row);
            }
            $csv=implode("\n", $rows);
        }
        if ($csv!='') {
            $csv=strip_tags($csv);
            $csv="\n$csv\n";
            $rnd=str_replace(".", "", microtime(true));
            $out.="<div class='dropdown2' data-toggle='collapse' data-target='#csv_data_$rnd'>
                <textarea cols='1' rows='6' onclick='this.focus();this.select()' class='lookup collapse' id='csv_data_$rnd' data-original-title='CSV Data'>$csv</textarea>
                </div>";
            if ($short!='') {
                $out.="<label>Export data</label><textarea cols='20' rows='10' class='well span12'>$csv</textarea>";
            }
        }
        return $out;
    }
    public function array2array($arr, $mame = 'label', $value = 'value')
    {
            $array2=array();
        foreach ($arr as $n => $val) {
            $array2[]= array("$mame" => "$n","$value"=>"$val");
        }
        return $array2;
    }
    public function array_push_associative(&$arr)
    {
        $args = func_get_args();
        array_unshift($args); // remove &$arr argument
        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $key => $value) {
                    $arr[$key] = $value;
                    $ret++;
                }
            }
        }
    }

    public function count_in_array($val = 0, $array = array())
    {
        $count = 0;
        foreach ($array as $key1 => $subarray) {
            foreach ($subarray as $key => $value) {
                if ($value == $val) {
                    $count++;
                }
            }
        }
        return $count;
    }
    public function text_replace($string, $array)
    {
        $pattern = "/<%([\w\W]*?)%>/";
        //$pattern = "/%([\w\W]*?)%/";
        preg_match_all($pattern, $string, $matches);
        $names=$matches[1];
        $names_tagged=$matches[0];
        $arr_find=array();
        $arr_replace=array();
        $i=0;
        foreach ($names_tagged as $name) {
            $val=$names[$i];
            $value=$array[$val];
            //$value= "NAME:$name, VAL:$value<br>";
            array_push($arr_find, $name);
            array_push($arr_replace, $value);
            $i++;
        }
        $out.=str_ireplace($arr_find, $arr_replace, $string);
        return $out;
    }


    public function get_request_val($orgqry, $var)
    {
        $res='';
        $orgqry=str_replace('&amp;', "&", $orgqry);
        $pairs=explode("&", $orgqry);
        foreach ($pairs as $pair) {
            $tokens=explode("=", $pair);
            if ($tokens[0]!='') {
                if ($tokens[0]==$var) {
                    $res=$tokens[1];
                }
            }
        }
        return $res;
    }



    public function clean_content($page)
    {
        $page = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $page);
        return $page;
    }







    public function url_short($longUrl)
    {

        // Get API key from : http://code.google.com/apis/console/
        $apiKey = $GLOBALS['google_api_key'];

        $postData = array('longUrl' => $longUrl, 'key' => $apiKey);
        $jsonData = json_encode($postData);

        $curlObj = curl_init();

        curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url');
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlObj, CURLOPT_HEADER, 0);
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt($curlObj, CURLOPT_POST, 1);
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, $jsonData);

        $response = curl_exec($curlObj);

        // Change the response json string to object
        $json = json_decode($response);

        curl_close($curlObj);

        return $json->id;
    }
    public function sql2json($sql)
    {
        if ($sql=='') {
            $sql="select 'No query defined' as error";
        }
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        while ($row = pg_fetch_array($cur)) {
            $json[] = $row;
        }
        $response=json_encode($json);
        return $response;
    }
    public function xml_chart($data)
    {
        @date_default_timezone_set("GMT");

        $writer = new XMLWriter();
        // Output directly to the user
        $writer->openURI('php://output');
        $writer->startDocument('1.0');
        $writer->setIndent(4);


        $writer->startElement('chart');
        $writer->writeAttribute('caption', 'Traffic Report');
        $writer->writeAttribute('subCaption', 'Six Months');
        $writer->writeAttribute('xaxisname', 'Month');
        $writer->writeAttribute('yaxisname', 'Unique Users');
        $writer->writeAttribute('clustered', '1');
        $writer->writeAttribute('showPlotBorder', '0');
        $writer->writeAttribute('zGapPlot', '50');
        $writer->writeAttribute('zDepth', '50');
        $writer->writeAttribute('cameraAngX', '9');
        $writer->writeAttribute('cameraAngY', '-27');
        $writer->writeAttribute('animate3D', '1');
        $writer->writeAttribute('formatNumberScale', '0');

            $writer->startElement('categories');
        foreach ($data as $key => $value) {
            $writer->startElement('category');
            $writer->writeAttribute('label', $key);
            $writer->endElement();//category
        }
            $writer->endElement();//categories

            $writer->startElement('dataset');
            $writer->writeAttribute('seriesName', 'Yahoo');
            $writer->writeAttribute('renderAs', 'column');
            $writer->writeAttribute('color', 'BADA66');
        foreach ($data as $key => $value) {
            $writer->startElement('set');
            $value=rand(100, 500);
            $writer->writeAttribute('value', $value);
            $writer->endElement();//set
        }
            $writer->endElement();//dataset

            $writer->startElement('styles');
                $writer->startElement('definition');
                    $writer->startElement('style');
                    $writer->writeAttribute('name', 'captionFont');
                    $writer->writeAttribute('type', 'font');
                    $writer->writeAttribute('size', '15');
                    $writer->endElement();//style
                $writer->endElement();//definition
                $writer->startElement('application');
                    $writer->startElement('apply');
                    $writer->writeAttribute('toObject', 'caption');
                    $writer->writeAttribute('styles', 'captionfont');
                    $writer->endElement();//apply
                $writer->endElement();//application
            $writer->endElement();//styles

        $writer->endElement();//chart
        $writer->endDocument();
        $writer->flush();
    }
    public function chart_js2($chart = 'MSLine', $w = 800, $h = 250, $strParam = "caption=Title;xAxisName=X;yAxisName=Y;numberPrefix=$", $data = array('one'=>1,'two'=>2))
    {
        include_once(FW_DIR.'/classes/FusionChart.class.php');
        $FC = new FusionCharts($chart, $w, $h);
        $FC->setSWFPath(FW_DIR.'/classes/FusionCharts/');
        $FC->setChartParams($strParam);

        $datasets=$data[datasets];
        foreach ($datasets as $key => $value) {
            $i++;
            $series=$datasets[$key];
        }
        foreach ($series as $key => $value) {
            $i++;
            $FC->addCategory("$key");
        }

        foreach ($datasets as $key => $value) {
            $i++;
            $series=$datasets[$key];
            $FC->addDataset("$key");
            foreach ($series as $key => $value) {
                $i++;
                $FC->addChartData("$value");
            }
        }

        $out.=  "   <script src='".APP_URI."/assets/js/FusionCharts/FusionCharts.js'></script>
            <script>FusionCharts.setCurrentRenderer('javascript');</script>";
        //ob_flush();flush();

        $out.= $FC->renderChart(false, false);
        unset($FC);
        return $out;
    }
    public function chart_js($chart = 'Line', $w = 800, $h = 250, $strParam = "caption=Title;xAxisName=X;yAxisName=Y;numberPrefix=$", $data = array('one'=>1,'two'=>2))
    {
        include_once(FW_DIR.'/classes/FusionChart.class.php');
        $FC = new FusionCharts($chart, $w, $h);
        $FC->setSWFPath(APP_URI.'/assets/swf/');
        $FC->setChartParams($strParam);
        foreach ($data as $key => $value) {
            $FC->addChartData($value, "label=$key");
        }
        $out.=  "   <script src='".APP_URI."/assets/js/FusionCharts/FusionCharts.js'></script>
            <script>FusionCharts.setCurrentRenderer('javascript');</script>";
        //ob_flush();flush();

        $out.= $FC->renderChart(false, false);
        unset($FC);
        return $out;
    }

    public function chart_js_new($chart = 'line', $w = 800, $h = 250, $id = 'chart-1', $jsonEncodedData)
    {
        include_once(FW_DIR.'/classes/fusioncharts.php');
        $FC = new \FusionCharts($chart, "$id", $w, $h, "fcid_$id", "json", $jsonEncodedData);
        //ob_flush();flush();
        $out="<div id='fcid_$id'></div>";
        $out.= $FC->render();
        unset($FC);
        return $out;
    }



    public function ucfirst_utf8($string, $e = 'utf-8')
    {
        if (function_exists('mb_strtoupper') && function_exists('mb_substr') && !empty($string)) {
            $string = mb_strtolower($string, $e);
            $upper = mb_strtoupper($string, $e);
            preg_match('#(.)#us', $upper, $matches);
            $string = $matches[1] . mb_substr($string, 1, mb_strlen($string, $e), $e);
        } else {
            $string = ucfirst($string);
        }
        return $string;
    }
    public function l($phrase)
    {
        return ucfirst_utf8(ln($phrase));
    }
    public function ln($phrase)
    {
        /* Static keyword is used to ensure the file is loaded only once */
        static $translations = null;
        /* If no instance of $translations has occured load the language file */
        if (is_null($translations)) {
            $lang_file = './lang/' . LANGUAGE . '.txt';
            if (!file_exists($lang_file)) {
                $lang_file ='./lang/' . 'en-us.txt';
            }
            $lang_file_content = file_get_contents($lang_file);
            /* Load the language file as a JSON object and transform it into an associative array */
            $translations = json_decode($lang_file_content, true);
        }
        if (!array_key_exists($phrase, $translations)) {
            return $phrase;
        } else {
            return $translations[$phrase];
        }
    }

    public function dl_file($file, $name, $exit = true)
    {
        //echo "<html>Test: $file, $name</html>"; exit;
        //First, see if the file exists

        if (!is_file($file)) {
            die("404 File <b>$file</b> not found! <a href='?act=tools&what=fix_file&name=file.pdf&id=0'>Rename to file?</a> ?act=tools&what=fix_file&name=file.pdf&id=");
        }

        //Gather relevent info about file
        $len = filesize($file);
        $filename = basename($file);
        if ($name=='') {
            $name=$filename;
        }
        $file_extension = strtolower(substr(strrchr($filename, "."), 1));

        //This will set the Content-Type to the appropriate setting for the file
        switch ($file_extension) {
            case "pdf":
                $ctype="application/pdf";
                break;
            case "exe":
                $ctype="application/octet-stream";
                break;
            case "zip":
                $ctype="application/zip";
                break;
            case "doc":
                $ctype="application/msword";
                break;
            case "docx":
                $ctype="application/vnd.openxmlformats-officedocument.wordprocessingml.document";
                break;
            case "xls":
                $ctype="application/vnd.ms-excel";
                break;
            case "xlsx":
                $ctype="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
                break;
            case "ppt":
                $ctype="application/vnd.ms-powerpoint";
                break;
            case "gif":
                $ctype="image/gif";
                break;
            case "png":
                $ctype="image/png";
                break;
            case "jpeg":
                $ctype="image/jpg";
                break;
            case "jpg":
                $ctype="image/jpg";
                break;
            case "mp3":
                $ctype="audio/mpeg";
                break;
            case "wav":
                $ctype="audio/x-wav";
                break;
            case "mpeg":
            case "mpg":
            case "mpe":
                $ctype="video/mpeg";
                break;
            case "mov":
                $ctype="video/quicktime";
                break;
            case "avi":
                $ctype="video/x-msvideo";
                break;
            case "txt":
                $ctype="text/plain";
                break;
            case "htm":
                $ctype="text/html; charset=cp-2151";
                break;
            //The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
            case "php":
            case "html":
                die("<b>Cannot be used for ". $file_extension ." files!</b>");
            break;

            default:
                $ctype="application/force-download";
        }
        //echo "$file, $name"; exit;
        //Begin writing headers
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer $file");
        //header("Media LONGDESC: $file");
        //header("META HTTP-EQUIV='File' CONTENT='$file'");

        //Use the switch-generated Content-Type
        header("Content-Type: $ctype");

        //Force the download
        //$header="Content-Disposition: attachment; filename=".$name.";";
        $header="Content-Disposition: inline; filename=\"$name\";";
        header($header);
        //header("Content-Disposition: inline");
        //header("filename=\"$name\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".$len);
        //@readfile($file);
        //echo "$file<br>"; exit;
        $open = fopen($file, 'r');
        fpassthru($open);
        fclose($open);
        if ($exit) {
            exit;
        }
    }
    public function F_num($sum)
    {
        $sum=str_ireplace(" ", "", $sum);
        $sum=str_ireplace(",", ".", $sum);
        return $sum;
    }

    public function cleanwhite($string)
    {
        return preg_replace('/\s+/', '', $string);
    }
    public function gettoken2($data, $word, $displace, $lenght)
    {
        $res=substr($data, 0, (strpos($data, $word)-strlen($word)-$displace-$lenght));
        return $res;
    }
    public function gettoken($data, $word, $displace, $lenght)
    {
        $res=substr($data, (strpos($data, $word)+strlen($word)+$displace), $lenght);
        return $res;
    }
    public function linkalize($link = '', $stop = '')
    {
        //$link="Not linked text:[url=?act=show&table=partners]thetext[/url] after link";
        $pos = strpos($link, "[/url]");
        $stop="stop";
        if ($pos !== false) {
            $stop="";
            $pos = strpos($link, "[url=");
            $textbefore=substr($link, 0, $pos);
            $data=$link;
            $word="[url=";
            $displace=0;
            $lenght=255;
            $part1=$this->gettoken($data, $word, $displace, $lenght);
            $pos2 = strpos($part1, "]");
            $thelink=substr($part1, 0, $pos2);

            $pos = strpos($link, "[/url]");
            $textafter=substr($link, $pos+6);

            //$textafter=substr($link,$pos);


            $pos2 = strpos($link, "[/url]");
            $pos1 = strpos($link, "[url=$thelink]");
            $length=strlen($thelink)+6;
            $thetext="$pos1-$pos2";
            $thetext=substr($link, $pos1+$length, $pos2-$pos1-$length);

            $linked="$textbefore <a href='$thelink'>$thetext</a> $textafter";
        } else {
            $linked="$link";
        }
        //$linked="<a href='$link'>".$link."</a>";
        /*  $linked = str_ireplace(
                               array (
                                       "[link]",
                                       "[/link]",
                                     ),
                               array (
                                       "<a href='$link'>",
                                       "</a>",
                                     ),
                               $link
                             );
        */
        if ($stop=="") {
            $linked=$this->linkalize($linked, $stop);
        }
        return $linked;
    }
    public function to_binary($bool)
    {
        if (($bool=='t')||($bool*1>0)) {
            return 1;
        } else {
            return 0;
        }
    }
    public function delete_upload($file)
    {
        $delete = @unlink($file);
            clearstatcache();
        if (@file_exists($file)) {
            $filesys = eregi_replace("/", "\\", $file);
            $delete = @system("del $filesys");
            clearstatcache();
            if (@file_exists($file)) {
                $delete = @chmod($file, 0775);
                $delete = @unlink($file);
                $delete = @system("del $filesys");
            }
        }
    }


    public function post_error($msg)
    {
        $_POST[backtoedit]=1;
        $_POST[noduplicate]=1;
        $GLOBALS[error_message].=$msg;
    }
    public function post_message($msg)
    {
        $GLOBALS[info_message].='<div class=\'alert\'>'.$msg.'</div>';
    }
    public function clientInSameSubnet($client_ip = false, $server_ip = false)
    {
        if (!$client_ip) {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        if (!$server_ip) {
            $server_ip = $_SERVER['SERVER_ADDR'];
        }
        // Extract broadcast and netmask from ifconfig
        if (!($p = popen("ifconfig", "r"))) {
            return false;
        }
        $out = "";
        while (!feof($p)) {
            $out .= fread($p, 1024);
        }
        fclose($p);
        // This is because the php.net comment public function does not
        // allow long lines.
        $match  = "/^.*".$server_ip;
        $match .= ".*Bcast:(\d{1,3}\.\d{1,3}i\.\d{1,3}\.\d{1,3}).*";
        $match .= "Mask:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/im";
        if (!preg_match($match, $out, $regs)) {
            return false;
        }
        $bcast = ip2long($regs[1]);
        $smask = ip2long($regs[2]);
        $ipadr = ip2long($client_ip);
        $nmask = $bcast & $smask;
        return (($ipadr & $smask) == ($nmask & $smask));
    }
    public function is_IP_local($ip)
    {
        $reserved_ips = array( // not an exhaustive list
            '167772160'  => 184549375,  /*    10.0.0.0 -  10.255.255.255 */
            '3232235520' => 3232301055, /* 192.168.0.0 - 192.168.255.255 */
            '2130706432' => 2147483647, /*   127.0.0.0 - 127.255.255.255 */
            '2851995648' => 2852061183, /* 169.254.0.0 - 169.254.255.255 */
            '2886729728' => 2887778303, /*  172.16.0.0 -  172.31.255.255 */
            '3758096384' => 4026531839, /*   224.0.0.0 - 239.255.255.255 */
        );

        $ip_long = sprintf('%u', ip2long($ip));

        foreach ($reserved_ips as $ip_start => $ip_end) {
            if (($ip_long >= $ip_start) && ($ip_long <= $ip_end)) {
                return true; //true
            }
        }
        return false;
    }



    public function app_procedures()
    {
        $procs=array();
        $proc_dir=FW_DIR.'/procedures/';
        $files1 = scandir($proc_dir);
        foreach ($files1 as $file) {
            if (($file!='..')&&($file!='.')&&(is_dir($proc_dir.$file))) {
                array_push($procs, $file);
                $title=$file;
                if ($file=='form') {
                    $title='add';
                }
                $res.="<br><div class='h'>$title</div>";
                $func_dir=FW_DIR.'/procedures/'.$file;
                $files2 = scandir($func_dir);
                foreach ($files2 as $file2) {
                    if (!is_dir($func_dir.$file2)) {
                        $parts=explode('.', $file2);
                        $filename=$parts[0];
                        $ext=$parts[1];
                        if (($ext=='php')&&($filename[0]!='_')&&($filename[0]!='-')) {
                            $res.="<a href='?act=$title&what=$filename'>$filename</a><br>";
                        }
                    }
                }
            }
        }
        //$res.=$this->pre_display($procs,'$procs');
        return $res;
    }
    public function boolean_select($field, $title)
    {
        $res="<SELECT NAME='$field' ID='id_$field'>
            <OPTION SELECTED VALUE=''>$title</OPTION>
            <OPTION  VALUE='t'>Yes</OPTION>
            <OPTION  VALUE='f'>No</OPTION>
            </SELECT>";
        return $res;
    }
    public function inline_js($class, $submitdata, $buttons = 0)
    {
        $json_submitdata=json_encode($submitdata);
        if ($buttons>0) {
            $buttons="cancel    : 'Cancel', submit    : 'OK',";
        } else {
            $buttons="cancel    : '', submit    : '',";
        }
        $res="
            $('$class').editable('?csrf=$GLOBALS[csrf]&act=save&what=inline_edit&plain=1', {
                name : 'value',
                select : true,
                submitdata : $json_submitdata,
                $buttons
                indicator : '<img src=\"".APP_URI."/assets/img/ajax-loader-bar.gif\">',
                tooltip   : 'Double Click to edit...',
                event     : 'dblclick',
                style  : 'inherit',
                //onblur: 'submit';
             });
        ";
        //event     : 'dblclick',
        return $res;
    }

    public function parseFromXML($htmlStr)
    {
        $xmlStr=str_replace('&lt;', '<', $htmlStr);
        $xmlStr=str_replace('&gt;', '>', $xmlStr);
        $xmlStr=str_replace('&quot;', '"', $xmlStr);
        $xmlStr=str_replace('&#39;', "'", $xmlStr);
        $xmlStr=str_replace('&amp;', "&", $xmlStr);
        return $xmlStr;
    }
    public function include_poject($project = 'is', $action = 'show:partners')
    {
        $actions=explode(':', $action);
        $act=$actions[0];
        $what=$actions[1];
        $inc=PROJECT_DIR.$project.DS."actions".DS.$act.DS."$what.php";
        //echo "$inc<br>";
        //include($inc);
        return $inc;
    }

    public function exportxls($csv)
    {
        if ($GLOBALS['settings']['use_xls_export']>0) {
            $lines = explode("\n", $csv);
            $dataArray = array();
            foreach ($lines as $line) {
                array_push($dataArray, str_getcsv($line, "\t"));
            }
            require_once CLASSES_DIR.'/PHPExcel/Classes/PHPExcel.php';
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->getProperties()->setCreator("IS")
                                         ->setLastModifiedBy("IS")
                                         ->setTitle("IS Export")
                                         ->setSubject("IS Export")
                                         ->setDescription("IS Export data")
                                         ->setKeywords("IS")
                                         ->setCategory("Data");

            $objPHPExcel->getActiveSheet()->fromArray($dataArray, null, 'A0');
            $objPHPExcel->getActiveSheet()->setTitle('report');
            $objPHPExcel->setActiveSheetIndex(0);
            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="report.xls"');
            header('Cache-Control: max-age=0');

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
        } return "XLS export turned off.";
    }


    public function altercart($action)
    {
        global $cart;
        //echo "2CART:$cart<br> $action"; exit;
        switch ($action) {
            case 'add':
                if ($cart) {
                    if ($_POST['id']) {
                        $cart .= ','.$_POST['id'];
                    } else {
                        $cart .= ','.$_GET['id'] ;
                    }
                } else {
                    if ($_POST['id']) {
                        $cart = $_POST['id'];
                    } else {
                        $cart = $_GET['id'];
                    }
                }
                break;
            case 'delete':
                if ($cart) {
                    $items = explode(',', $cart);
                    $newcart = '';
                    foreach ($items as $item) {
                        if ($_GET['id'] != $item) {
                            if ($newcart != '') {
                                $newcart .= ','.$item;
                            } else {
                                $newcart = $item;
                            }
                        }
                    }
                    $cart = $newcart;
                }
                break;
            case 'clear':
                if ($cart) {
                    $items = explode(',', $cart);
                    $newcart = '';
                    $cart = "";
                }
                break;
            case 'update':
                if ($cart) {
                    $newcart = '';
                    foreach ($_POST as $key => $value) {
                        if (stristr($key, 'qty')) {
                            $id = str_replace('qty', '', $key);
                            $items = ($newcart != '') ? explode(',', $newcart) : explode(',', $cart);
                            $newcart = '';
                            foreach ($items as $item) {
                                if ($id != $item) {
                                    if ($newcart != '') {
                                        $newcart .= ','.$item;
                                    } else {
                                        $newcart = $item;
                                    }
                                }
                            }
                            for ($i=1; $i<=$value; $i++) {
                                if ($newcart != '') {
                                    $newcart .= ','.$id;
                                } else {
                                    $newcart = $id;
                                }
                            }
                        }
                    }
                }
                $cart = $newcart;
                break;
        }
        session_start();
        $_SESSION['cart'] = $cart;
        //echo $this->pre_display($cart,"cart");
        session_write_close();
        //$_SESSION['cart']=$cart;
    }
    public function show_run_time($txt)
    {
        global $starttime, $access;
        if ($access['view_runtime']) {
            $mtime = microtime();
            $mtime = explode(" ", $mtime);
            $mtime = $mtime[1] + $mtime[0];
            $endtime = $mtime;
            $uptime = round($endtime - $GLOBALS['starttime'], 3);
            $out.= "<span class='badge'>RUN: $uptime sec $txt</span><br>";
        }
        return $out;
    }
    public function get_run_time()
    {
        $now=microtime(true);
        $runtime=round($now-$GLOBALS['starttime'], 2);
        return $runtime;
    }


    public function time_marker($txt = 'runtime')
    {
        $now=microtime(true);
        $runtime=round($now-$GLOBALS['starttime'], 2);
        $GLOBALS['time_marker'][$txt]=$runtime;
        return true;
    }


    public function get_microtime()
    {
        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        return round($mtime, 2);
    }
    public function genshow($tablename)
    {
        $sql="SELECT
                  *
                  FROM $tablename ";
            //echo $sql;
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $out= "<textarea name='content_show' cols='100' rows='40' class='span12'>\n";
        $out.= "<?php
    //Show $tablename
    if(\$sortby==''){\$sortby=\"id asc\";}

    \$tmp=\$this->html->readRQcsv('ids');
    if (\$tmp!=''){\$sql.=\" and id in (\$tmp)\";}

    \$tmp=\$this->html->readRQn('list_id');
    if (\$tmp>0){\$sql.=\" and list_id=\$tmp\";}

    \$sql1=\"select *\";
    \$sql=\" from \$what a where id>0 \$sql\";
    \$sqltotal=\$sql;
    \$sql = \"\$sql order by \$sortby\";
    \$sql2=\" limit \$limit offset \$offset;\";
    \$sql=\$sql1.\$sql.\$sql2;
    //\$out.= \$sql;
    ";
        $i = pg_num_fields($cur);
        for ($j = 0; $j < $i; $j++) {
            //echo "column $j\n";
            $fieldname = pg_field_name($cur, $j);
            $fieldtype = pg_field_type($cur, $j);
            //$out.= "F:$fieldname:$fieldtype\n";
            $ok="";
            $fieldlist.="'$fieldname',";
        }
        $out.="\$fields=array($fieldlist);
    //\$sort= \$fields;
    \$out=\$this->html->tablehead(\$what,\$qry, \$order, 'no_addbutton', \$fields,\$sort);

    if (!(\$cur = pg_query(\$sql))) {\$this->html->HT_Error( pg_last_error().\"<br><b>\".\$sql.\"</b>\" );}
    \$rows=pg_num_rows(\$cur);if(\$rows>0)\$csv.=\$this->data->csv(\$sql);
    while (\$row = pg_fetch_array(\$cur)) {
        \$i++;
        \$class='';
        //\$type=\$this->data->get_name('listitems',\$row[type]);
        if(\$row[id]==0)\$class='d';
        \$out.= \"<tr class='\$class'>\";
        \$out.= \$this->html->edit_rec(\$what,\$row[id],'ved',\$i);
        \$out.= \"<td id='\$what:\$row[id]' class='cart-selectable' reference='\$what'>\$row[id]</td>\";
        \$out.= \"<td onMouseover=\\\"showhint('\$row[descr]', this, event, '400px');\\\">\$row[name]</td>\";
        \$out.= \"<td>\$row[date]</td>\";
        \$out.= \"<td>\$type</td>\";
        \$out.= \"<td class='n'>\".\$this->html->money(\$row[amount]).\"</td>\";
        \$out.= \"</tr>\";
        \$totals[2]+=\$row[qty];
        if (\$allids) \$allids.=','.\$what.':'.\$row[id]; else \$allids.=\$what.':'.\$row[id];
        \$this->livestatus(str_replace(\"\\\"\",\"'\",\$this->html->draw_progress(\$i/\$rows*100)));
    }
    \$this->livestatus('');
    include(FW_DIR.'/helpers/end_table.php');
    ";


        $out.="</textarea>";
        //echo $out;
        return $out;
    }
    public function gendetails($tablename)
    {
        $sql="SELECT
                  *
                  FROM $tablename ";
            //echo $sql;
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $out= "<textarea name='content_details' cols='100' rows='40' class='span12'>\n";
        $out.= "<?php\n//Details $tablename
    \$res=\$this->db->GetRow(\"select * from \$what where id=\$id\");
    \$partner=\$this->data->detalize('partners', \$res[partner_id]);
    \$date=\$this->html->readRQd('date',1);
    \$out.= \"<h1>\$res[name]</h1>\";
    \$out.=\$this->data->details_bar(\$what,\$id);

    \$out.= \"<table class='table table-morecondensed table-notfull'>\";";
        $i = pg_num_fields($cur);
        for ($j = 0; $j < $i; $j++) {
            //echo "column $j\n";
            $fieldname = pg_field_name($cur, $j);
            $fieldtype = pg_field_type($cur, $j);
            //$out.= "F:$fieldname:$fieldtype\n";
            $ok="";

            if ((($fieldtype=='int4')||($fieldtype=='int2')||($fieldtype=='bool')||($fieldtype=='int8')||($fieldtype=='float8')||($fieldtype=='integer')||($fieldtype=='float'))&&($ok=="")) {
                $out.= "\$out.=\"<tr><td class='mr'><b>".str_replace('_', ' ', ucfirst($fieldname)).": </b></td><td class='mt'>\$res[$fieldname]</td></tr>\";\n";
            } else {
                $out.= "\$out.=\"<tr><td class='mr'><b>".str_replace('_', ' ', ucfirst($fieldname)).": </b></td><td class='mt'>\$res[$fieldname]</td></tr>\";\n";
            }

            if ($fieldname!='id') {
                $out4.="\t\t'$fieldname'=>$$fieldname,\n";
            }
        }
        $out4=substr($out4, 0, -2)."\n";

        $out.= "$out1";
        $out.= "\$out.=\"</table>\";

    if(\$res[descr])\$out.= \"Description:<br><pre>\$res[descr]</pre>\";\n";
        $out.= "
    \$dname=\$this->data->docs2obj(\$id,\$what);
    \$out.=\"<b>Documents:</b> \$dname<br>\";
    \$out.=\$this->show_docs2obj(\$id, \$what);

    \$_POST[tablename]=\$what;
    \$_POST[refid]=\$id;
    \$_POST[reffinfo]=\"&tablename=\$what&refid=\$id\";
    \$out.=\$this->show('schedules');
    \$out.=\$this->show('comments');
    \$out.=\$this->report('posts');
    \$out.=\$this->report('db_changes');
    \$body.=\$out;
    ";


        $out.="</textarea>";
        //echo $out;
        return $out;
    }
    public function gensave($tablename)
    {
        $sql="SELECT
                  *
                  FROM $tablename ";
            //echo $sql;
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $out= "<textarea name='content_save' cols='100' rows='40' class='span12'>\n";
        $out.= "<?php\n//Save $tablename\n";

        $i = pg_num_fields($cur);
        for ($j = 0; $j < $i; $j++) {
            //echo "column $j\n";
            $fieldname = pg_field_name($cur, $j);
            $fieldtype = pg_field_type($cur, $j);
            //$out.= "F:$fieldname:$fieldtype\n";
            $ok="";
            if (($fieldtype=='date')&&($ok=="")) {
                $ok=1;
                $out1.="$$fieldname=\$this->html->readRQd('$fieldname',1);\n";
            } else {
                if ((($fieldtype=='int4')||($fieldtype=='int2')||($fieldtype=='bool')||($fieldtype=='int8')||($fieldtype=='float8')||($fieldtype=='integer')||($fieldtype=='float'))&&($ok=="")) {
                    $out1.="$$fieldname=\$this->html->readRQn('$fieldname');\n";
                } else {
                    $out1.="$$fieldname=\$this->html->readRQ('$fieldname');\n";
                }
            }
            if ($fieldname!='id') {
                $out4.="\t'$fieldname'=>$$fieldname,\n";
                $out5.="\t//$$fieldname=\$res[$fieldname];\n";
            }
        }
        $out4=substr($out4, 0, -2)."\n";
        $out5="
    if(\$id==0){
        //\$user_id=\$GLOBALS[uid];
        //\$date='now()';
    }else{
        \$res=\$this->data->get_row(\$what,\$id);
    $out5}
    ";


        $out.= "$out1
    $out5_deleted
    \$vals=array(\n$out4);
    echo \$this->html->pre_display(\$_POST,'Post'); echo \$this->html->pre_display(\$vals,'Vals');exit;
    if(\$id==0){\$id=\$this->db->insert_db(\$what,\$vals);}else{\$id=\$this->db->update_db(\$what,\$id,\$vals);}
    \$body.=\$out;
    ";


        $out.="</textarea>";
        //echo $out;
        return $out;
    }
    public function genform($tablename)
    {
        $sql="SELECT
                  *
                  FROM $tablename ";
            //echo $sql;
        if (!($cur = pg_query($sql))) {
            $this->error(pg_last_error()."<br><b>".$sql."</b>");
        }
        $out= "<textarea name='content_form' cols='100' rows='40' class='span12'>\n";

        $i = pg_num_fields($cur);
        for ($j = 0; $j < $i; $j++) {
            //echo "column $j\n";
            $fieldname = pg_field_name($cur, $j);
            $fieldtype = pg_field_type($cur, $j);
            $ok="";
            $label=ucfirst(str_replace('_', ' ', $fieldname));
            if ($fieldname != 'id') {
                //$out.= "F:$fieldname:$fieldtype\n";
                if (($fieldname=='type')&&($ok=="")) {
                    $ok=1;

                    $outfields.= "  \";
                        \$$fieldname=\$this->data->listitems('$fieldname',\$res[$fieldname],'$fieldname');
                        \$out.= \"<label>$label</label>\$$fieldname\n";
                }
                if ((($fieldname=='partnerid')||($fieldname=='partner_id')||($fieldname=='bank_id')||($fieldname=='bankid')||($fieldname=='sender_id')||($fieldname=='receiver_id')||($fieldname=='sender_id')||($fieldname=='receiver'))&&($ok=="")) {
                    $ok=1;
                    $tokens=explode("_", $fieldname);
                    $outfields.= "$$fieldname=\$this->data->partner_form('$fieldname',\$res[$fieldname],'$tokens[0]'); ";
                    $outfields.= "\$out.=\"$$fieldname"."[out]\";\n";
                }
                if (($this->contains('_id', $fieldname))&&($ok=="")) {
                    $ok=1;
                    $tokens=explode("_", $fieldname);
                    array_pop($tokens);
                    $label=implode(' ', $tokens);
                    $list_alias=implode('_', $tokens);
                    //$$fieldname=\$this->html->htlist('$fieldname',\"SELECT id, name FROM $tokens[0]s WHERE id>0 ORDER by id\",\$res[$fieldname],'Select $tokens[0]');
                    $outfields.= "
    $$fieldname=\$this->data->listitems('$fieldname',\$res[$fieldname],'$list_alias','span12');
        \$out.= \"<label>".ucfirst($label)."</label>$$fieldname\";\n";
                }
                if ((($fieldname=='descr')||($fieldname=='addinfo')||($fieldname=='values')||($fieldname=='data')||($fieldname=='key'))&&($ok=="")) {
                    $ok=1;
                    $outfields.="\$out.=\$this->html->form_textarea('$fieldname',\$res[$fieldname],'$label','',0,'','span12');\n";
                }
                if ((($fieldtype=='date')||($fieldtype=='datetime')||($fieldtype=='timestamp'))&&($ok=="")) {
                    $ok=1;
                    $outfields.="\$out.=\$this->html->form_date('$fieldname',\$res[$fieldname],'$label','',0,'span12');\n";
                }
                if ((($fieldtype=='bool'))&&($ok=="")) {
                    $ok=1;
                    $outfields.="\$out.=\$this->html->form_chekbox('$fieldname',\$res[$fieldname],'$label','',0,'span12');\n";
                }
                if ($ok=="") {
                    $ok=1;
                    $outfields.="\$out.=\$this->html->form_text('$fieldname',\$res[$fieldname],'$label','',0,'span12');\n";
                }
            }
        }
        $outhead.= "<?php
    //Edit $tablename
    if (\$act=='edit'){
        \$sql=\"select * from \$what WHERE id=\$id\";
        \$res=\$this->db->GetRow(\$sql);
    }else{
        \$sql=\"select * from \$what WHERE id=\$refid\";
        \$res2=\$this->db->GetRow(\$sql);
        \$res[active]='t';
    }

    \$form_opt['well_class']=\"span11 columns form-wrap\";

    \$out.=\$this->html->form_start(\$what,\$id,'',\$form_opt);
    \$out.=\"<hr>\";

    \$out.=\$this->html->form_hidden('reflink',\$reflink);
    \$out.=\$this->html->form_hidden('id',\$id);
    \$out.=\$this->html->form_hidden('reference',\$reference);
    \$out.=\$this->html->form_hidden('refid',\$refid);

    $outfields

    \$out.=\$this->html->form_confirmations();
    \$out.=\$this->html->form_submit('Save');
    \$out.=\$this->html->form_end();
    ";
        $out.="$outhead
    \$body.=\$out;
    </textarea>";

        //echo $out;
        return $out;
    }
    public function validate_pass($password)
    {
        //ctype_alnum($password) // numbers & digits only
        if (strlen($password)>7 // at least 8 chars
        && strlen($password)<17 // at most 16 chars
        && preg_match('`[A-Z]`', $password) // at least one upper case
        && preg_match('`[a-z]`', $password) // at least one lower case
        && preg_match('`[0-9]`', $password) // at least one digit
        ) {
            return true;
        } else {
            return false;
        }
    }
    public function calc_interest_value($data)
    {
        /*
        Compound Interest
        Let the yearly rate of interest be i (as a fraction, e.g. a rate of 6% would correspond to i=0.06),
        the amount of the principal be P, the number of years be n, the number of times per year
        that the interest is compounded be q, and the amount after n years be A. Then

            A = P(1+[i/q])nq.

        Then the present value is given by

            P = A(1+[i/q])-nq.

        To find the interest rate i, use

            i = q([A/P]1/nq - 1).

        To determine how many years are needed to reach a given amount,

            n = log(A/P)/(q log[1+(i/q)]).

        Interest may be compounded quarterly, monthly, weekly, daily, or even more frequently.
        As the frequency of compounding increases, the amount A increases, but ever more slowly
        -- in fact it approaches a limit with continuous compounding.
        The formulas for this situation are found by taking the limit of the
        formulas above as q increases without bound. They take the form

            A = Pein,
            P = Ae-in,
            i = log(A/P)/(n log[e]),
            n = log(A/P)/(i log[e]).


        */
        //$M = $P*( $r*(1 + $r)^$n ) / ((1 + $r)^$n – 1);

        return $m;
    }




    public function calc_interest($data)
    {
        $f=__FUNCTION__;
        return include(FW_DIR.'/helpers/ff.php');
    }
    public function calc_loan($data)
    {
        $f=__FUNCTION__;
        return include(FW_DIR.'/helpers/ff.php');
    }
    public function plan_loan($data)
    {
        $f=__FUNCTION__;
        return include(FW_DIR.'/helpers/ff.php');
    }
    public function analyze_loan()
    {
        $f=__FUNCTION__;
        return include(FW_DIR.'/helpers/ff.php');
    }


    public function normalize_list($list, $delim = ',')
    {
        $array_tmp = array_filter(array_map('trim', explode($delim, $list)), 'strlen');
        $res=implode($delim, $array_tmp);
        return $res;
    }

    public function unscramble($coded)
    {
        $key = "SXGWLZPDOKFIVUHJYTQBNMACERxswgzldpkoifuvjhtybqmncare";
        $key = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456abcdefghijklmnopqrstuvwxyz";
        $key=$GLOBALS['settings']['scrable_key'];

        $uncoded = "";
        $chr;
        for ($i = 0; $i <= strlen($coded) - 1; $i++) {
            $chr = $coded{$i};
            $ord = ord($chr);
          //$coded .= ($ord >= 65 and $ord <= 122 ) ?
            $uncoded .= ($chr >= "a" and $chr <= "z" or $chr >= "A" and $chr <= "Z") ?
            chr(65 + strpos($key, $chr)) :
            $chr;
        }
        return $uncoded;
    }



    public function scramble($uncoded)
    {
        //$uncoded = mb_convert_encoding($uncoded, 'UTF-16');
        $key = "SXGWLZPDOKFIVUHJYTQBNMACERxswgzldpkoifuvjhtybqmncare";
        //$key = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456abcdefghijklmnopqrstuvwxyz";
        $key=$GLOBALS['settings']['scrable_key'];
        $coded = "";
        $chr;
        for ($i = 0; $i <= strlen($uncoded) - 1; $i++) {
            $chr = $uncoded{$i};
            $ord = ord($chr);
            //  $coded .= $i.":".$chr.":".$ord."|";

            $coded .= ($ord >= 65 and $ord <= 122 ) ?
                $key{($ord - 65)} :
            //$chr."|";
            $chr;
        }
        return $coded;
    }

    public function decodeStr($coded)
    {
        $key = "SXGWLZPDOKFIVUHJYTQBNMACERxswgzldpkoifuvjhtybqmncare";
        $key=$GLOBALS['settings']['scrable_key'];
        $uncoded = "";
        $chr;
        for ($i = strlen($coded) - 1; $i >= 0; $i--) {
            $chr = $coded{$i};
            $uncoded .= ($chr >= "a" and $chr <= "z" or $chr >= "A" and $chr <= "Z") ?
                chr(65 + strpos($key, $chr) % 26) :
            $chr;
        }
        return $uncoded;
    }
    public function from_cyr($text)
    {
        $cyr = array(
            'ж',  'ч',  'щ',   'ш',  'ю',  'а', 'б', 'в', 'г', 'д', 'e', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ъ', 'ь', 'я',
            'Ж',  'Ч',  'Щ',   'Ш',  'Ю',  'А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ъ', 'Ь', 'Я');
        $lat = array(
        'zh', 'ch', 'sht', 'sh', 'yu', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'y', 'x', 'ya',
        'Zh', 'Ch', 'Sht', 'Sh', 'Yu', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'c', 'Y', 'X', 'Ya');
        return str_replace($cyr, $lat, $text);
    }
    public function ru2lat($str)
    {
        $tr = array(
            "А"=>"A", "Б"=>"B", "В"=>"V", "Г"=>"G", "Д"=>"D",
            "Е"=>"E", "Ё"=>"Yo", "Ж"=>"Zh", "З"=>"Z", "И"=>"I",
            "Й"=>"J", "К"=>"K", "Л"=>"L", "М"=>"M", "Н"=>"N",
            "О"=>"O", "П"=>"P", "Р"=>"R", "С"=>"S", "Т"=>"T",
            "У"=>"U", "Ф"=>"F", "Х"=>"Kh", "Ц"=>"Ts", "Ч"=>"Ch",
            "Ш"=>"Sh", "Щ"=>"Sch", "Ъ"=>"", "Ы"=>"Y", "Ь"=>"",
            "Э"=>"E", "Ю"=>"Yu", "Я"=>"Ya", "а"=>"a", "б"=>"b",
            "в"=>"v", "г"=>"g", "д"=>"d", "е"=>"e", "ё"=>"yo",
            "ж"=>"zh", "з"=>"z", "и"=>"i", "й"=>"j", "к"=>"k",
            "л"=>"l", "м"=>"m", "н"=>"n", "о"=>"o", "п"=>"p",
            "р"=>"r", "с"=>"s", "т"=>"t", "у"=>"u", "ф"=>"f",
            "х"=>"kh", "ц"=>"ts", "ч"=>"ch", "ш"=>"sh", "щ"=>"sch",
            "ъ"=>"", "ы"=>"y", "ь"=>"", "э"=>"e", "ю"=>"yu",
            "я"=>"ya", " "=>"-", "."=>"", ","=>"", "/"=>"-",
            ":"=>"", ";"=>"","—"=>"", "–"=>"-"
            );
            return strtr($str, $tr);
    }

    public function find_similar($input = '', $words = array(), $shortest = -1)
    {
        $met1=metaphone($input, 6);

        foreach ($words as $word) {
            $met2=metaphone($word, 6);
            $lev = levenshtein($input, $word);
            $lev2 = levenshtein($met1, $met2);
            //$res[out].="($lev) $word<br>";
            if ($lev == 0) {
                $closest = $word;
                $shortest = $lev;
                break;
            }
            if ($lev2 <= 1) {
                $closest = $word;
                $shortest = $lev2;
                $closest_arr[]  = $word;
            }
            if ($lev <= $shortest || $shortest < 0) {
                $closest  = $word;
                $shortest = $lev;
            }
        }

        if ($shortest>0) {
            foreach ($words as $word) {
                $lev = levenshtein($input, $word);
                if ($lev <= $shortest+1) {
                    $closest_arr[]  = $word;
                    //$shortest = $lev;
                }
            }
        }
        $res[closest_arr]=$closest_arr;
        $res[closest]=$closest;
        $res[shortest]=$shortest;

        return $res;
    }

    public function find_similar_splitted($input = '', $words = array(), $shortest = -1)
    {
        $input=metaphone($input, 6);
        $found=0;
        foreach ($words as $word) {
            $bits=explode(' ', $word);
            foreach ($bits as $bit) {
                $bit=metaphone($bit, 6);
                $lev = levenshtein($input, $bit);
                if ($lev<=0) {
                    $closest  = $word;
                    $shortest = $lev;
                    //echo "=== $closest [$input, $bit] ($lev)<br>";
                    $closest_arr[]  = $word;
                    $found++;
                }
            }
            //$res[out].="($lev) $word<br>";
            if ($break>0) {
                break;
            }
        }
        if ($found==0) {
            foreach ($words as $word) {
                $bits=explode(' ', $word);
                foreach ($bits as $bit) {
                    $bit=metaphone($bit, 6);
                    $lev = levenshtein($input, $bit);
                    if ($lev<=1) {
                        $closest  = $word;
                        $shortest = $lev;
                        //echo "=== $closest [$input, $bit] ($lev)<br>";
                        $closest_arr[]  = $word;
                    }
                }
                //$res[out].="($lev) $word<br>";
                if ($break>0) {
                    break;
                }
            }
        }


        $res[closest_arr]=$closest_arr;
        $res[closest]=$closest;
        $res[shortest]=$shortest;

        return $res;
    }
        /////==============To resove with HTML class=========\\\\\\\

    public function tablehead($what, $qry, $order, $addbutton, $fields, $sort, $tips)
    {
            $out.="
                <table class='table table-bordered table-striped-tr table-morecondensed tooltip-demo  table-notfull' id='sortableTable'>
                <thead  class='c'>
                <tr>
                    ";
        if ($what!='') {
            $out.="<th><a href='?act=search&what=$what' class='c'><i class='icon-search'></i></a></th>";
        }
            $i=0;
        foreach ($fields as $field) {
            $field=strtolower($field);
            if ($tips[$i]!="") {
                $tip="data-original-title='$tips[$i]'";
            } else {
                $tip="";
            }
            if ($sort[$i]!="") {
                $out.=" <th class='tooltip-test' $tip><a href='?$qry&sortby=$sort[$i]$order' TITLE='Sort'>".ucfirst($field)."</a></th>";
            } else {
                $out.=" <th class='tooltip-test' $tip>".ucfirst($field)."</th>";
            }
            $i++;
        }
        if ($what!='') {
            $out.="<th style='text-align: center; width: 50px;'>$addbutton</th>";
        }
            $out.="</tr>
                    </thead>
                    <tbody>";
        return $out;
    }
    public function tablefoot($i, $totals, $totalrecs, $opt)
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
        $out.="</tbody>
                <thead>
                 <tr>
                    <th>$totalrecs</th>";
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
                </thead>";
        if ($continue!=1) {
            $out.="      </table>";
        } else {
            $out.="<tbody>";
        }
        return $out;
    }

    public function money($sum = 0, $curr = '', $opt = '', $dec = 2)
    {
        $zero='--';
        if ($sum < 0) {
            $div="neg";
        }
        if ($sum > 0) {
            $div="pos";
        }
        //if ($opt == ''){$do="<span class='$div'>";$dc="</span>";}else{$do="";$dc="";}
        if ($this->contains('no_thousands', $opt)) {
            $ts='';
        } else {
            $ts=' ';
        }
        if ($this->contains('no_zeros', $opt)) {
            $zero='';
        }
        if ($this->contains('force_zeros', $opt)) {
            $zero='0';
        }

        if ($sum == 0) {
            $div="zero";
            $sum=$zero;
            $curr="";
        } else {
            $sum=$do.number_format($sum, $dec, '.', $ts)." $curr".$dc;
        }
        return $sum;
    }

    public function draw_progress($val1, $val2)
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
    public function bytes2h($bytes)
    {
        $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
        $base = 1024;
        $class = min((int)log($bytes, $base), count($si_prefix) - 1);
        return sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $si_prefix[$class];
    }
    public function randomPassword($chars)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $chars; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
    public function gen_Password($caps = 3, $smalls = 3, $digs = 4)
    {
        $digits='1234567890';
        $capitals='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $letters='abcdefghijklmnopqrstuvwxyz';
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array

        $capitalsLength = strlen($capitals) - 1; //put the length -1 in cache
        for ($i = 0; $i < $caps; $i++) {
            $n = rand(0, $capitalsLength);
            $pass[] = $capitals[$n];
        }
        $lettersLength = strlen($letters) - 1; //put the length -1 in cache
        for ($i = 0; $i < $smalls; $i++) {
            $n = rand(0, $lettersLength);
            $pass[] = $letters[$n];
        }
        $digitsLength = strlen($digits) - 1; //put the length -1 in cache
        for ($i = 0; $i < $digs; $i++) {
            $n = rand(0, $digitsLength);
            $pass[] = $digits[$n];
        }


        return implode($pass); //turn the array into a string
    }

    public function file_upload_error($error_integer)
    {
        $upload_errors = array(
            // http://php.net/manual/en/features.file-upload.errors.php
            UPLOAD_ERR_OK               => "No errors.",
            UPLOAD_ERR_INI_SIZE     => "Larger than upload_max_filesize.",
            UPLOAD_ERR_FORM_SIZE    => "Larger than form MAX_FILE_SIZE.",
            UPLOAD_ERR_PARTIAL      => "Partial upload.",
            UPLOAD_ERR_NO_FILE      => "No file.",
            UPLOAD_ERR_NO_TMP_DIR => "No temporary directory.",
            UPLOAD_ERR_CANT_WRITE => "Can't write to disk.",
            UPLOAD_ERR_EXTENSION    => "File upload stopped by extension."
            );
        return $upload_errors[$error_integer];
    }

    public function sanitize_file_name($filename)
    {
        // Remove characters that could alter file path.
        // I disallowed spaces because they cause other headaches.
        // "." is allowed (e.g. "photo.jpg") but ".." is not.
        $filename = preg_replace("/([^A-Za-z0-9_\-\.]|[\.]{2})/", "", $filename);
        // basename() ensures a file name and not a path
        $filename = basename($filename);
        return $filename;
    }

        // Returns the file permissions in octal format.
    public function file_permissions($file)
    {
        // fileperms returns a numeric value
        $numeric_perms = fileperms($file);
        // but we are used to seeing the octal value
        $octal_perms = sprintf('%o', $numeric_perms);
        return substr($octal_perms, -4);
    }

        // Returns the file extension of a file
    public function file_extension($file)
    {
        $path_parts = pathinfo($file);
        return $path_parts['extension'];
    }

        // Searches the contents of a file for a PHP embed tag
        // The problem with this check is that file_get_contents() reads
        // the entire file into memory and then searches it (large, slow).
        // Using fopen/fread might have better performance on large files.
    public function file_contains_php($file)
    {
        $contents = file_get_contents($file);
        $position = strpos($contents, '<?php');
        return $position !== false;
    }

    public function normalize_filename($filename)
    {

        $arr1=explode('.', $filename);
        $extention=$arr1[(count($arr1)-1)];

        //echo $this->pre_display($arr1, "arr1");
        if (($extention=='')||(strlen($extention)>10)) {
            $extention='txt';
        }
        $arr2=explode('.'.$extention, $filename);
        $cleanname=$arr2[0];

        $cleanname=$this->ru2lat($cleanname);
        $extention=$this->ru2lat($extention);


        $cleanname=str_replace(' ', '_', $cleanname);
        $cleanname=substr($cleanname, 0, 20);

        $filename=$cleanname.'.'.$extention;
        $filename=$this->sanitize_file_name($filename);


        return $filename;
    }


    public function upload_file($field_name = 'file', $upload_path = '/', $max_file_size = 1048576, $allowed_mime_types = array('image/png', 'image/gif', 'image/jpg', 'image/jpeg'), $allowed_extensions = array('png', 'gif', 'jpg', 'jpeg'), $check_is_image = 1, $check_for_php = 1)
    {
        //$max_file_size = 1048576; // 1 MB expressed in bytes

        if (isset($_FILES[$field_name])) {
            // Sanitize the provided file name.
            $file_name = sanitize_file_name($_FILES[$field_name]['name']);
            $file_extension = file_extension($file_name);

            // Even more secure to assign a new name of your choosing.
            // Example: 'file_536d88d9021cb.png'
            // $unique_id = uniqid('file_', true);
            // $new_name = "{$unique_id}.{$file_extension}";

            $file_type = $_FILES[$field_name]['type'];
            $tmp_file = $_FILES[$field_name]['tmp_name'];
            $error = $_FILES[$field_name]['error'];
            $file_size = $_FILES[$field_name]['size'];

            // Prepend the base upload path to prevent hacking the path
            // Example: $file_name = '/etc/passwd' becomes harmless
            $file_path = $upload_path . DS . $file_name;

            if ($error > 0) {
                // Display errors caught by PHP
                echo "Error: " . file_upload_error($error);
            } elseif (!is_uploaded_file($tmp_file)) {
                echo "Error: Does not reference a recently uploaded file.<br />";
            } elseif ($file_size > $max_file_size) {
                // PHP already first checks php.ini upload_max_filesize, and
                // then form MAX_FILE_SIZE if sent.
                // But MAX_FILE_SIZE can be spoofed; check it again yourself.
                echo "Error: File size is too big.<br />";
            } elseif (!in_array($file_type, $allowed_mime_types)) {
                echo "Error: Not an allowed mime type.<br />";
            } elseif (!in_array($file_extension, $allowed_extensions)) {
                // Checking file extension prevents files like 'evil.jpg.php'
                echo "Error: Not an allowed file extension.<br />";
            } elseif ($check_is_image && (getimagesize($tmp_file) === false)) {
                // getimagesize() returns image size details, but more importantly,
                // returns false if the file is not actually an image file.
                // You obviously would only run this check if expecting an image.
                echo "Error: Not a valid image file.<br />";
            } elseif ($check_for_php && file_contains_php($tmp_file)) {
                // A valid image can still contain embedded PHP.
                echo "Error: File contains PHP code.<br />";
            } elseif (file_exists($file_path)) {
                // if destination file exists it will be over-written
                // by move_uploaded_file()
                echo "Error: A file with that name already exists in target location.<br />";
                // Could rename or force user to rename file.
                // Even better to store in uniquely-named subdirectories to
                // prevent conflicts.
                // For example, if the database record ID for an image is 1045:
                // "/uploads/profile_photos/1045/uploaded_image.png"
                // Because no other profile_photo has that ID, the path is unique.
            } else {
                // Success!
                echo "File was uploaded without errors.<br />";
                echo "File name is '{$file_name}'.<br />";
                echo "File references an uploaded file.<br />";

                // Two ways to get the size. Should always be the same.
                echo "Uploaded file size was {$file_size} bytes.<br />";
                // filesize() is most useful when not working with uploaded files.
                $tmp_filesize = filesize($tmp_file); // always in bytes
                echo "Temp file size is {$tmp_filesize} bytes.<br />";

                echo "Temp file location: {$tmp_file}<br />";


                // move_uploaded_file has is_uploaded_file() built-in
                if (move_uploaded_file($tmp_file, $file_path)) {
                    echo "File moved to: {$file_path}<br />";

                    // remove execute file permissions from the file
                    if (chmod($file_path, 0644)) {
                        echo "Execute permissions removed from file.<br />";
                        $file_permissions = file_permissions($file_path);
                        echo "File permissions are now '{$file_permissions}'.<br />";
                    } else {
                        echo "Error: Execute permissions could not be removed.<br />";
                    }
                }
            }
        }
    }

    public function get_class_methods_list($scope, $class)
    {
        $openf="public function ";
        $closef="(";

        $corefile=$scope.'classes/'.$class.'.php';
        $whats=array();
        $i=0;
        $file_c = file_get_contents($corefile);
        $func=explode($openf, $file_c);
        foreach ($func as $function) {
            $fname=explode($closef, $function);
            if (($fname[0]!='')&&($i>0)) {
                $whats[$i]=$fname[0];
            }
            $i++;
        }
        sort($whats);
        $res = $this->pre_display($whats, $class.' methods');
        return $res;
    }
    public function get_file_list($dir, $ext, $remove_ext = 0)
    {
        $path    = '/tmp';
        $files = scandir($dir);
        $list=array();
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if ($ext!='') {
                    if ($this->contains('.'.$ext, $file)) {
                        if ($remove_ext>0) {
                            //$file=$this->text_replace($file, array('.php'=>'.txt'));
                            $tmp=explode('.', $file);
                            array_pop($tmp);
                            $file=implode('.', $tmp);
                            $list[] = $file;
                        } else {
                            $list[] = $file;
                        }
                    }
                } else {
                    $list[] = $file;
                }
            }
        }
        return $list;
    }
    public function word_format($data)
    {
        $data=str_ireplace("[tab]", '</w:t></w:r><w:r><w:tab/></w:r><w:r><w:t>', $data);
        //$data=str_ireplace("[tab]",'<text:tab/>',$data);
        $data=str_ireplace("[nl]", "\n", $data);
        return $data;
    }
    public function factor($n)
    {
        $factors_array = array();
        for ($x = 1; $x <= sqrt(abs($n)); $x++) {
            if ($n % $x == 0) {
                $z = $n/$x;
                array_push($factors_array, $x, $z);
            }
        }
        rsort($factors_array);
        return $factors_array;
    }
    public function factor2($n)
    {
        $factors_array = array();
        for ($x = 1; $x <= sqrt(abs($n)); $x++) {
            if ($n % $x == 0) {
                $z = $n/$x;
                array_push($factors_array, $x);
            }
        }
        rsort($factors_array);
        return $factors_array;
    }
    public function say($amount, $hide_cetnts = 0)
    {

        $amountdec=floor($amount);
        $amountfloat=round(($amount-$amountdec)*100, 0);
        if ($nw=='') {
            include_once(FW_DIR.'/classes/Numbers/Words.php');
            $nw = new \Numbers_Words();
        }
        $amountsaydec=$nw->toWords($amountdec);

        $amountsay=ucfirst(strtolower(str_ireplace("-", " ", $amountsaydec." & $amountfloat/100 ")));
        if ($hide_cetnts>0) {
            $amountsay=ucfirst(strtolower(str_ireplace("-", " ", $amountsaydec)));
        }

        return $amountsay;
    }


    public function csvfile_to_array($filename = '', $delimiter = "\t", $index_filed = 0)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header) {
                    $header = $row;
                } elseif ($index_filed==0) {
                    $data[] = array_combine($header, $row);
                } else {
                    $data[$row[$index_filed-1]] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

    public function csv_to_array($data = '', $delimiter = "\t")
    {
        $header = null;
        $csv=[];
        $rows = str_getcsv($data, "\n");
        foreach ($rows as &$row) {
            $i++;
            $row = str_getcsv($row, $delimiter);

            if (!$header) {
                $header = $row;
                $fileds=count($row);
            } else {
                $diff=$fileds-count($row);
                if ($diff>0) {
                    for ($j=0; $j < $diff; $j++) {
                        $row[]='';
                    }
                }
                $csv[] = array_combine($header, $row);
            }
        }
        return $csv;
    }

    public function array_to_csv($arr = array(array('a'=>1)), $delimiter = "\t", $enclosure = '"')
    {
        $headers=$arr[0];
        foreach ($headers as $key => $val) {
            $header[]=$key;
        }
        $csv.=implode($delimiter, $header)."\n";

        foreach ($arr as $id => $rowed) {
            $row=array();
            foreach ($rowed as $key => $val) {
                $row[]=$val;
            }
            $csv.=implode($delimiter, $row)."\n";
        }
        return $csv;
    }



    public function xls_range($range = 'A1:B2')
    {
        $bits=explode(':', $range);
        $r0=preg_split('#(?<=[a-z])(?=\d)#i', $bits[0]);
        $r1=preg_split('#(?<=[a-z])(?=\d)#i', $bits[1]);
        $arr=array($r0[1],$r1[1],$r0[0],$r1[0]);
        return $arr;
    }
    public function sql_to_array($sql = 'select 1', $key = '')
    {
        $result = $this->sql->query($sql) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql);
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $res[]=$row;
        }
        $result->finalize();
        if ($key!='') {
            foreach ($res as $key_index => $value) {
                $new_res[$value[$key]]=$value;
            }
            $res=$new_res;
        }
        return $res;
    }

    public function SQLite3_table_exists($tablename)
    {
        $name=$this->SQLite3_querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='$tablename';");
        $result=($name!='')?true:FLASE;
        return $result;
    }

    public function SQLite3_field_exists($table_name, $field_name)
    {
        $sql="PRAGMA table_info('$table_name')";
        $result = $this->sql->query($sql) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql);
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($row[name]==$field_name) {
                $filed_exists=true;
            }
        }
        return $filed_exists;
    }

    public function SQLite3_query($sql)
    {
        $result=$this->sql->query($sql) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql);
        return $result;
    }
    public function SQLite3_querySingle($sql)
    {
        $result=$this->sql->querySingle($sql) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql);
        return $result;
    }

    public function array_to_sql($array = array(), $tablename)
    {
        //echo $this->pre_display($array,$tablename);

        $i=0;
        foreach ($array as $row_key => $row) {
            $sql_insert_values='';
            foreach ($row as $col_key => $col) {
                $field_name=$col_key;
                $field_value=$col;
                $field_type_prim=gettype($col);
                if ($this->dates->is_date($field_value)) {
                    $field_type_prim='date';
                }

                switch ($field_type_prim) {
                    case 'date':
                        $field_type='DATE';
                        break;
                    case 'string':
                        $field_type='TEXT';
                        break;
                    case 'double':
                        $field_type='REAL';
                        break;
                    default:
                        $field_type='TEXT';
                }
                $field_name=str_replace('#', 'number', $field_name);
                $field_name=str_replace("'", '', $field_name);
                $field_name=str_replace(' ', '_', $field_name);
                $field_value=str_replace("'", '', $field_value);
                $field_name=$this->sql->escapeString($field_name);
                $field_value=$this->sql->escapeString($field_value);

                if (!is_string($field_name)) {
                    $field_name="N_$field_name";
                }
                if (strtolower($field_name)=='id') {
                    $field_name="{$field_name}_";
                }
                //if(is_numeric())
                //echo "$field_name = $field_value ($field_type)<br>";
                if ($field_name!='') {
                    $sql_create_fields.="   $field_name $field_type,\n";
                    $sql_insert_values.="'$field_value',";
                }
            }
            if ($i==0) {
                $sql_create="CREATE TABLE IF NOT EXISTS $tablename (\n";
                $sql_create.="  id INTEGER PRIMARY KEY   AUTOINCREMENT,\n";
                $sql_create.="$sql_create_fields";
                $sql_create=rtrim($sql_create, ",\n");
                $sql_create.="\n)";
                //echo $this->pre_display($sql_create,'sql_create');
                $this->sql->exec($sql_create) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql_create);
            }
            $sql_insert="INSERT INTO $tablename VALUES (NULL,";
            $sql_insert.=$sql_insert_values;
            $sql_insert=rtrim($sql_insert, ",");
            $sql_insert.=");\n";
            $this->sql->exec($sql_insert) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql_insert);
            //echo $this->pre_display($sql_insert,'sql_insert');
            //$sql_inserts.=$sql_insert;
            $i++;
            //echo $this->pre_display($row,$row_key);
        }
    }



    public function error($msg, $title = 'ERROR', $class = 'alert-error')
    {
        if ($this->contains('not an error', $msg)>0) {
            return;
        }

        if ($this->contains('alert-', $class)>0) {
            $class="alert $class";
        }
        if ($title!='') {
            $header="<h2>$title</h2>";
        }
        echo "<div class='$class'>$header$msg</div>";
        exit;
    }

    public function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }



    public function render_url($url, $file = "/data/integra/tmp/localhost.png", $height = 325)
    {
        $url="http://integra.lan/?act=show&what=deals&offline_token=1234&no_wrap=1";
        $phantomjs="/usr/local/bin/phantomjs";
        $params="--web-security=false";
        $js_file=APP_URI."/assets/js/rasterize.js"; //"/data/integra/tmp/rasterize.js";
        $options="960px*{$height}px";
        $command="$phantomjs $params $js_file \"$url\" \"$file\" \"$options\"";
        exec($command);
        echo "$command<br>";
    }
    public function read_data_xlsx($filename, $toc = array())
    {
        $xlsx='test.xlsx';
        $file_data=[];
        $file=$filename.'.xlsx';
        $file=$filename;
        if (!($this->contains('/tmp/', $filename))) {
            $file=APP_DIR.'data'.DS.$filename.'.xlsx';
        }
        if (!(file_exists($file))) {
            $this->error("$file not found");
        }
        if (count($toc)==0) {
            $file_data['toc'] = $this->excel_to_array($file, 'toc', array(1,20,'A','D'), 1);
            $toc=$file_data['toc'];
        }

        //echo $this->pre_display($file_data);
        foreach ($toc as $item => $values) {
            if ($item!='') {
                //echo $this->pre_display($values);
                $xls_range=$this->xls_range($values['range']);
                $file_data[strtolower(str_ireplace(' ', '_', $values['table_name']))] = $this->excel_to_array($file, $values['sheet'], $xls_range, $values['key']);
                //$this->array_to_sql($file_data[$values['table_name']],$values['key']);
            }
        }
        //echo $this->pre_display($file_data,'$file_data');//exit;
        foreach ($file_data as $name => $values) {
            //if($GLOBALS['log_level']>2)echo $this->pre_display($values,"$name");
            $name=strtolower(str_ireplace(' ', '_', $name));
            $this->array_to_sql($values, $name);
        }
        return $file_data;
    }


    public function excel_to_array($inputFileName = 'test.xlsx', $sheetname = '', $range = array(), $index_filed = 0)
    {
        if (!file_exists($inputFileName)) {
            exit(pathinfo($inputFileName, PATHINFO_BASENAME)." not found.");
        }
        $data=array();
        $header = null;
        require_once CLASSES_DIR.'/PHPExcel/Classes/PHPExcel/IOFactory.php';


        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        //$out.= 'File ',pathinfo($inputFileName,PATHINFO_BASENAME),' has been identified as an ',$inputFileType,' file<br />';

        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setLoadSheetsOnly($sheetname);
        $input = $objReader->load($inputFileName);


        //$out.= '<hr />';

        //$out.= "<h1>$filename</h1>";
        //$out.= '<hr />';
        foreach ($input->getWorksheetIterator() as $worksheet) {
            $worksheetTitle     = $worksheet->getTitle();
            $highestRow         = $worksheet->getHighestRow(); // e.g. 10
            $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
            $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
            $nrColumns = ord($highestColumn) - 64;
            if (count($range)==0) {
                $range=array(0,$highestRow,'A',$highestColumn);
            }
            if ($range[0]>$highestRow) {
                $range[0]=0;
            }
            if ($range[1]>$highestRow) {
                $range[1]=(int)$highestRow;
            }

            $first=\PHPExcel_Cell::columnIndexFromString($range[2])-1;
            $last=\PHPExcel_Cell::columnIndexFromString($range[3]);
            if ($first>$highestColumnIndex) {
                $first=0;
            }
            if ($last>$highestColumnIndex) {
                $last=$highestColumnIndex;
            }

            $range_set[]=$range[0];
            $range_set[]=$range[1];
            $range_set[]=$first;
            $range_set[]=$last;

            //var_dump($range_set);


            $out.= "<h3 class='foldered'><i class='icon-th-large tooltip-test addbtn' data-original-title=''></i>$worksheetTitle</h3>";
            $out.= "<table class='table table-bordered table-striped-tr table-morecondensed tooltip-demo  table-notfull' id='sortTableExample'>";
            for ($row=$range_set[0]; $row<=$range_set[1]; $row++) {
                $out.= "<tr>";
                $out.= "<td class='n'>$row</td>";
                $row_array=array();
                for ($col=$range_set[2]; $col<$range_set[3]; $col++) {
                    $tmp = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                    try {
                        $val = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                    } catch (PHPExcel_Calculation_Exception $e) {
                        try {
                            $val = $worksheet->getCellByColumnAndRow($col, $row)->getOldCalculatedValue();
                        } catch (PHPExcel_Calculation_Exception $e) {
                            $val='';
                            $out.=("<span class='badge red'>Error in cell '$tmp'".$e->getMessage()."</span><br>");
                        }
                    }

                    $class=(is_numeric($val))?'n':'';
                    $out.= "<td class='$class'>$val</td>";
                    $row_array[]=$val;
                }
                if (!$header) {
                    $row_array = array_map('strtolower', $row_array);
                    $row_array=str_ireplace(' ', '_', $row_array);
                    $header = $row_array;
                } elseif ($index_filed==0) {
                    $data[] = array_combine($header, $row_array);
                } else {
                    $data[$row_array[$index_filed-1]] = array_combine($header, $row_array);
                }
                $out.= "</tr>";
            }
            $out.= "</table>";
        }


        //$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
        //var_dump($sheetData);
        unset($objReader);
        //echo $out;
        return $data;
    }
    public function read_xlsx($inputFileName = 'test.xlsx')
    {
        if (!file_exists($inputFileName)) {
            exit(pathinfo($inputFileName, PATHINFO_BASENAME)." not found.");
        }
        //include 'PHPExcel/IOFactory.php';
        require_once CLASSES_DIR.'/PHPExcel/Classes/PHPExcel/IOFactory.php';
        $inputFileName = APP_DIR.'data/Входные данные_2.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        echo 'File ',pathinfo($inputFileName, PATHINFO_BASENAME),' has been identified as an ',$inputFileType,' file<br />';

        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        //$objReader->setPreCalculateFormulas(FALSE);
        $input = $objReader->load($inputFileName);


        echo '<hr />';

        echo "<h1>$filename</h1>";
        echo '<hr />';
        foreach ($input->getWorksheetIterator() as $worksheet) {
            $worksheetTitle     = $worksheet->getTitle();
            $highestRow         = $worksheet->getHighestRow(); // e.g. 10
            $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
            $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
            $nrColumns = ord($highestColumn) - 64;

            echo "<h3 class='foldered'><i class='icon-th-large tooltip-test addbtn' data-original-title=''></i>$worksheetTitle</h3>";
            echo "<table class='table table-bordered table-striped-tr table-morecondensed tooltip-demo  table-notfull' id='sortTableExample'>";
            for ($row=0; $row<=$highestRow; $row++) {
                echo "<tr>";
                for ($col=0; $col<$highestColumnIndex; $col++) {
                    $tmp = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                    //if($this->contains('VLOOKUP',$tmp))$val=$tmp; else
                    try {
                        $val = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                    } catch (PHPExcel_Calculation_Exception $e) {
                        try {
                            $val = $worksheet->getCellByColumnAndRow($col, $row)->getOldCalculatedValue();
                        } catch (PHPExcel_Calculation_Exception $e) {
                            $val='';
                            echo("<span class='badge red'>Error in cell '$tmp'".$e->getMessage()."</span><br>");
                        }
                    }

                    $class=(is_numeric($val))?'n':'';
                    echo "<td class='$class'>$val</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }


        //$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
        //var_dump($sheetData);
    }
    public function test_xlsx_read()
    {


        //error_reporting(E_ALL);
        //ini_set('display_errors', TRUE);
        //ini_set('display_startup_errors', TRUE);

        define('EOL', (PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

        date_default_timezone_set('Europe/London');

        /** Include PHPExcel_IOFactory */
        //require_once dirname(__FILE__) . '/../Classes/PHPExcel/IOFactory.php';
        require_once CLASSES_DIR.'/PHPExcel/Classes/PHPExcel/IOFactory.php';

        $file=APP_DIR.'data/test.xlsx';

        if (!file_exists($file)) {
            exit("$file is missing." . EOL);
        }

        // Use PCLZip rather than ZipArchive to read the Excel2007 OfficeOpenXML file
        //PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);

        //echo date('H:i:s') , " Load from Excel2007 file" , EOL;
        $callStartTime = microtime(true);

        $objPHPExcel = \PHPExcel_IOFactory::load($file);

        $callEndTime = microtime(true);
        $callTime = $callEndTime - $callStartTime;
        //echo 'Call time to read Workbook was ' , sprintf('%.4f',$callTime) , " seconds" , EOL;
        // Echo memory usage
        //echo date('H:i:s') , ' Current memory usage: ' , (memory_get_usage(true) / 1024 / 1024) , " MB" , EOL;


        //echo date('H:i:s') , " Write to Excel2007 format" , EOL;
        $callStartTime = microtime(true);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="test.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        //$objWriter->save(str_replace('.php', '.xlsx', __FILE__));
        $objWriter->save('php://output');

        $callEndTime = microtime(true);
        $callTime = $callEndTime - $callStartTime;

        //echo date('H:i:s') , " File written to " , str_replace('.php', '.xlsx', pathinfo(__FILE__, PATHINFO_BASENAME)) , EOL;
        //echo 'Call time to write Workbook was ' , sprintf('%.4f',$callTime) , " seconds" , EOL;
        // Echo memory usage
        //echo date('H:i:s') , ' Current memory usage: ' , (memory_get_usage(true) / 1024 / 1024) , " MB" , EOL;


        // Echo memory peak usage
        //echo date('H:i:s') , " Peak memory usage: " , (memory_get_peak_usage(true) / 1024 / 1024) , " MB" , EOL;

        // Echo done
        //echo date('H:i:s') , " Done writing file" , EOL;
        //echo 'File has been created in ' , getcwd() , EOL;
    }
    public function array_add_heder_row($data)
    {
        if (!isset($data['0'])) {
            return;
        }
        $headers[]=array_keys($data['0']);
        $data=array_merge($headers, $data);
        return $data;
    }
    public function txt2array($txt = '')
    {
        $data=array();
        $rows=explode("\n", $txt);
        foreach ($rows as $row) {
            $cells=explode("\t", $row);
            $data[]=$cells;
        }
        return $data;
    }
    public function test_xlsx()
    {
            //require_once '../core/PHPExcel/Classes/PHPExcel.php';
            require_once CLASSES_DIR.'/PHPExcel/Classes/PHPExcel.php';
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->getProperties()->setCreator("IS")
                                         ->setLastModifiedBy("IS")
                                         ->setTitle("IS Export")
                                         ->setSubject("IS Export")
                                         ->setDescription("IS Export data")
                                         ->setKeywords("IS")
                                         ->setCategory("Data");

            //$objPHPExcel->getActiveSheet()->fromArray($dataArray, NULL, 'A0');
            $objPHPExcel->getActiveSheet()->setTitle('report');
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A1', 'Hello')
                        ->setCellValue('B2', 'world!')
                        ->setCellValue('C1', 'Hello')
                        ->setCellValue('D2', 'world!');

            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="test.xlsx"');
            header('Cache-Control: max-age=0');

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
    }
    public function text_stretch($text, $spaces = '    ')
    {
        $text=strtoupper($text);
        //$text = implode('I ',explode('I',$text));
        $text = implode($spaces, str_split($text));
        $text = implode('I  ', explode('I', $text));
        return $text;
    }
    public function array_remove($source = array(), $remove = array())
    {
        $result=array_diff_key($source, array_flip($remove));
        return $result;
    }

    public function array_only($source = array(), $only = array())
    {
        $bad_keys =array_diff_key($source, array_flip($only));
        $result =array_diff_key($source, $bad_keys);
        return $result;
    }

    public function array_rename_key($array = array(), $old_name = 'name', $new_name = '1')
    {
        $array[$new_name] = $array[$old_name];
        //echo "$array[$old_name]<br>";
        unset($array[$old_name]);
        return $array;
    }

    public function transpose_matrix($data)
    {
        $retData = array();
        foreach ($data as $row => $columns) {
            foreach ($columns as $row2 => $column2) {
                $retData[$row2][$row] = $column2;
            }
        }
        return $retData;
    }

    public function invert_matrix($A)
    {
        /// @todo check rows = columns
        $n = count($A);
        // get and append identity matrix
        $I = $this->identity_matrix($n);
        for ($i = 0; $i < $n; ++ $i) {
            $A[$i] = array_merge($A[$i], $I[$i]);
        }
        // forward run
        for ($j = 0; $j < $n-1; ++ $j) {
            // for all remaining rows (diagonally)
            for ($i = $j+1; $i < $n; ++ $i) {
                // if the value is not already 0
                if ($A[$i][$j] !== 0) {
                    // adjust scale to pivot row
                    // subtract pivot row from current
                    $scalar = $A[$j][$j] / $A[$i][$j];
                    for ($jj = $j; $jj < $n*2; ++ $jj) {
                        $A[$i][$jj] *= $scalar;
                        $A[$i][$jj] -= $A[$j][$jj];
                    }
                }
            }
        }
        // reverse run
        for ($j = $n-1; $j > 0; -- $j) {
            for ($i = $j-1; $i >= 0; -- $i) {
                if ($A[$i][$j] !== 0) {
                    $scalar = $A[$j][$j] / $A[$i][$j];
                    for ($jj = $i; $jj < $n*2; ++ $jj) {
                        $A[$i][$jj] *= $scalar;
                        $A[$i][$jj] -= $A[$j][$jj];
                    }
                }
            }
        }
        // last run to make all diagonal 1s
        /// @note this can be done in last iteration (i.e. reverse run) too!
        for ($j = 0; $j < $n; ++ $j) {
            if ($A[$j][$j] !== 1) {
                $scalar = 1 / $A[$j][$j];
                for ($jj = $j; $jj < $n*2; ++ $jj) {
                    $A[$j][$jj] *= $scalar;
                }
            }
        }
        // take out the matrix inverse to return
        $Inv = array();
        for ($i = 0; $i < $n; ++ $i) {
            $Inv[$i] = array_slice($A[$i], $n);
        }
        return $Inv;
    }

    public function identity_matrix($n)
    {
        $I = array();
        for ($i = 0; $i < $n; ++ $i) {
            for ($j = 0; $j < $n; ++ $j) {
                $I[$i][$j] = ($i == $j) ? 1 : 0;
            }
        }
        return $I;
    }

    public function matrix_multiply($Array1, $Array2)
    {
            $rows2 = count($Array2);
        if (is_array($Array2[0])) {
            $dim2 = 2;
            $columns2 = count($Array2[0]);
        } else {
            $dim2 = 1;
            $columns2 = 1;
        }

            $rows1 = count($Array1);
        if (is_array($Array1[0])) {
            $dim1 = 2;
            $columns1 = count($Array1[0]);
        } else {
            $dim1 = 1;
            if ($rows2 == 1) {
                $columns1 = 1;
            } else {
                $columns1 = $rows1;
                $rows1 = 1;
            }
        }

        for ($i=0; $i<$rows1; $i++) {
            for ($j=0; $j<$columns2; $j++) {
                    $a = 0;
                for ($M=0; $M<$columns1; $M++) {
                    if ($dim1 == 2) {
                        $b = $Array1[$i][$M];
                    } elseif ($rows2 == 1) {
                        $b = $Array1[$i];
                    } else {
                        $b = $Array1[$M];
                    }
                        $c = $Array2[$M];
                    if ($dim2 == 2) {
                        $c = $c[$j];
                    }
                        $a = $a + $b * $c;
                }
                if ($dim2 == 2) {
                    $ArrayMultipli[$i][$j] = $a;
                } else {
                    $ArrayMultipli[$i] = $a;
                }
            }
        }
            return $ArrayMultipli;
    }



    public function rangeToStr($items, $itemSep = ', ', $rangeSep = '-', $sort = true)
    {
        if (!is_array($items)) {
            $items = explode(',', $items);
        }
        if ($sort) {
            sort($items);
        }
        $point = null;
        $range = false;
        $str = '';
        foreach ($items as $i) {
            if ($point === null) {
                $str .= $i;
            } elseif (($point + 1) == $i) {
                $range = true;
            } else {
                if ($range) {
                    $str .= $rangeSep . $point;
                    $range = false;
                }
                $str .= $itemSep . $i;
            }
            $point = $i;
        }
        if ($range) {
            $str .= $rangeSep . $point;
        }

        return $str;
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


    function tag($title = '', $type = 'h2', $class = '', $id = '')
    {
        $result='';
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


    public function get_func_list()
    {


        $methods = get_class_methods($this);
        sort($methods);
        $out.= $this->pre_display($methods, 'funcs methods');
        $out.= $this->get_class_methods_list(APP_DIR, 'project');



        $arr=htmlspecialchars(print_r($_SERVER, true));
        $out.= $this->pre_display($arr, 'Servers variables');
        return $out;
    }
}
