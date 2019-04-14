<?php
namespace Rozdol\Router;

use Rozdol\Html\Html;
use Rozdol\Db\Db;
use Rozdol\Data\Data;
use Rozdol\Dates\Dates;
use Rozdol\Utils\Utils;
use Rozdol\Utils\Crypt;
use Rozdol\Utils\Utf8;
use Rozdol\Utils\Comm;

class Router
{
    private static $hInstance;

    public static function getInstance($db)
    {
        if (!self::$hInstance) {
            self::$hInstance = new Router($db);
        }
        return self::$hInstance;
    }

    public $db;
    public $utils;
    public $nw;

    public function __construct(DB $db)
    {
        $this->db=$db;
        // include(FW_DIR.'vendor'.DS.'Numbers'.DS.'Words.php');
        // $this->nw = new Numbers_Words();
        $this->utils = Utils::getInstance();
        $this->dates = Dates::getInstance();

        $this->utf8 = utf8::getInstance();
        $this->data = Data::getInstance($this->db);

        $project_class=APP_DIR.DS.'classes'.DS.'Project.php';
        if (file_exists($project_class)) {
            require($project_class);
            $this->project = Project::getInstance($this->db);
        }
        $numbers_class=CLASSES_DIR.DS.'Numbers'.DS.'Words.php';
        if (file_exists($numbers_class)) {
            require($numbers_class);
            $this->nw = new \Numbers_Words();
        }



        $this->crypt = Crypt::getInstance();
        $this->html = Html::getInstance();
        $this->comm = Comm::getInstance();
    }
    function run()
    {
        //echo "Works";
        // echo $this->html->pre_display($_GET, "GET", '', 0);
        $this->html->request_normalize();
        $GLOBALS['time_marker']['after_request_normalize']=round(microtime(true)-$GLOBALS['starttime'], 2);
        // echo $this->html->pre_display($_GET, "GET", '', 0);
        if ($GLOBALS[act]=='welcome') {
            $content[options][noecho]=1;
            //echo $this->html->pre_display($content,"content");
            echo $this->html->wrappedMessage($this->html->putLogin($content), ' ', '');
        }
        if ($GLOBALS[act]=='login') {
            $message=$this->data->login();
            echo $this->html->wrappedMessage($message);
        }
        if ($GLOBALS[act]=='logout') {
            $message=$this->data->logout();
            echo $this->html->wrappedMessage($message);
        }
        if ($GLOBALS[act]=='offline') {
            if ($project) {
                $message=$this->project->offline();
            }
        }

        if (!$GLOBALS[raw_data]) {
            $this->html->putHeader();
            $GLOBALS['time_marker']['after_header']=round(microtime(true)-$GLOBALS['starttime'], 2);
            $nomenu=$this->html->readRQn('hide_menu');
            if (($nomenu==0)&&(!$GLOBALS[settings][hide_menu])) {
                $this->html->putTopBar($this->putMenu(), $this->data->userinfo());
            }
            $GLOBALS['time_marker']['after_topbar']=round(microtime(true)-$GLOBALS['starttime'], 2);
            $this->html->putErorMessage();
            $this->html->putInfoMessage();
            $GLOBALS['time_marker']['after_messages']=round(microtime(true)-$GLOBALS['starttime'], 2);
            $this->html->putWrap_in();
        }
        $GLOBALS['time_marker']['after_headers']=round(microtime(true)-$GLOBALS['starttime'], 2);
        if ($GLOBALS[uid]==-1) {
            unset($content);
            //$this->html->putTestBS();

            //echo "DDDD:$content<br>";
            $this->html->putLogin($content);
        } else {
            if (($GLOBALS[cart]<>'')&&(!$GLOBALS[raw_data])) {
                echo $this->data->shoppingcart();
            }
            if ($GLOBALS[act]=='') {
                $this->home();
            } elseif (method_exists($this, $GLOBALS[act])) {
                //if(!in_array($GLOBALS[act],array('login','logout')))
                echo $this->{$GLOBALS[act]}($GLOBALS[what]);
            } else {
                $this->html->notFound($GLOBALS[act].','.$GLOBALS[what]);
            }
        }
        $GLOBALS['time_marker']['after_actions']=round(microtime(true)-$GLOBALS['starttime'], 2);
        if (!$GLOBALS[raw_data]) {
            $this->html->putWrap_out();
            $this->html->putDebugMessage();
            $GLOBALS['time_marker']['before_footer']=round(microtime(true)-$GLOBALS['starttime'], 2);
            $nofooter=$this->html->readRQn('hide_footer');
            if (($nofooter==0)&&(!$GLOBALS[settings][hide_footer])) {
                $this->html->putFooter($content);
            }
        }
        $this->data->long_query();
        $GLOBALS['time_marker']['after_footer']=round(microtime(true)-$GLOBALS['starttime'], 2);

        return true;
    }


    public function putMenu()
    {
        if (($GLOBALS[uid]>0)&&(!$GLOBALS[settings][no_menu])) {
            $source_file= APP_DIR . DS .'helpers'. DS .'menu.html';
            if (file_exists($source_file)) {
                $html=file_get_contents($source_file);
                $source_file= APP_DIR . DS .'helpers'. DS .'menu_admin.html';
                if ((file_exists($source_file))&&($GLOBALS['access']['main_admin'])) {
                    $html.=file_get_contents($source_file);
                    //$html.=$this->data->menu($GLOBALS[gid]);
                }
            } else {
                if ($GLOBALS['settings']['fast_menu']>0) {
                    $html=$this->data->fast_menu($GLOBALS[gid]);
                } else {
                    $html=$this->data->menu($GLOBALS[gid]);
                }
            }
        }
        //require(FW_DIR.'/helpers/menu.php'); else
        //require(FW_DIR.'/helpers/menu_empty.php');
        return $html;
    }

    public function home()
    {
        echo $this->report('home_page');
    }

    public function show($what)
    {
        global $access;
        $accessitemchk="view_$what";
        if (($access[$accessitemchk])) {
            return $this->dispatch($what, __FUNCTION__, $accessitemchk);
        } else {
            return $this->data->noAccess($accessitemchk);
        }
    }
    public function details($what)
    {
        global $access;
        $accessitemchk="view_$what";
        $tables=explode(',', $GLOBALS[tables_chk_access]);
        if (in_array($what, $tables)) {
            $id=$this->html->readRQn('id');
            $GLOBALS['allow_details']=$this->data->isallowed($what, $id);
            if (($GLOBALS['allow_details']==0)) {
                echo $this->html->notFound("You have no access.");
                $this->data->DB_log("TRIED view details restricted on $what id=$id");
                exit;
            }
        }

        if (($access[$accessitemchk])) {
            return $this->dispatch($what, __FUNCTION__, $accessitemchk);
        } else {
            return $this->data->noAccess($accessitemchk);
        }
    }
    public function search($what)
    {
        global $access;
        $accessitemchk="view_$what";
        if (($access[$accessitemchk])) {
            return $this->dispatch($what, __FUNCTION__, $accessitemchk);
        } else {
            return $this->data->noAccess($accessitemchk);
        }
    }
    public function save($what)
    {
        global $access;
        $accessitemchk="edit_$what";

        $tables=explode(',', $GLOBALS[tables_chk_access]);
        if (in_array($what, $tables)) {
            $id=$this->html->readRQn('id');
            if($id>0){
                $GLOBALS['allow_details']=$this->data->isallowed($what, $id);
                if (($GLOBALS['allow_details']==0)) {
                    echo $this->html->notFound("You have no access.");
                    $this->data->DB_log("TRIED view details restricted on $what id=$id");
                    exit;
                }
            }
        }

        if (($access[$accessitemchk])) {
            return $this->dispatch($what, __FUNCTION__, $accessitemchk);
        } else {
            return $this->data->noAccess($accessitemchk);
        }
    }
    public function add($what)
    {
        global $access;
        $accessitemchk="edit_$what";

        $tables=explode(',', $GLOBALS[tables_chk_access]);
        if (in_array($what, $tables)) {
            $id=$this->html->readRQn('id');
            if($id>0){
                $GLOBALS['allow_details']=$this->data->isallowed($what, $id);
                if (($GLOBALS['allow_details']==0)) {
                    echo $this->html->notFound("You have no access.");
                    $this->data->DB_log("TRIED view details restricted on $what id=$id");
                    exit;
                }
            }
        }

        $GLOBALS[action_folder]="form";
        if (($access[$accessitemchk])) {
            return $this->dispatch($what, 'form', $accessitemchk);
        } else {
            return $this->data->noAccess($accessitemchk);
        }
    }
    public function edit($what)
    {
        global $access;
        $GLOBALS[action_folder]="form";
        $accessitemchk="edit_$what";
        $tables=explode(',', $GLOBALS[tables_chk_access]);
        if (in_array($what, $tables)) {
            $id=$this->html->readRQn('id');
            if (($id>0)&&($this->data->isallowed($what, $id)==0)) {
                echo $this->html->notFound("You have no access.");
                $this->data->DB_log("TRIED edit restricted on $what id=$id");
                exit;
            }
        }
        if (($access[$accessitemchk])) {
            return $this->dispatch($what, 'form', $accessitemchk);
        } else {
            return $this->data->noAccess($accessitemchk);
        }
    }
    public function report($what)
    {
        global $access;
        $accessitemchk="report_$what";
        if (($access[$accessitemchk])) {
            return $this->dispatch($what, __FUNCTION__, $accessitemchk);
        } else {
            return $this->data->noAccess($accessitemchk);
        }
    }
    public function delete($what)
    {
        global $access;
        $accessitemchk="edit_$what";

        $tables=explode(',', $GLOBALS[tables_chk_access]);
        if (in_array($what, $tables)) {
            $id=$this->html->readRQn('id');
            $GLOBALS['allow_details']=$this->data->isallowed($what, $id);
            if (($GLOBALS['allow_details']==0)) {
                echo $this->html->notFound("You have no access.");
                $this->data->DB_log("TRIED view details restricted on $what id=$id");
                exit;
            }
        }

        if (($access[$accessitemchk])) {
            return $this->dispatch($what, __FUNCTION__, $accessitemchk);
        } else {
            return $this->data->noAccess($accessitemchk);
        }
    }
    public function append($what)
    {
        global $access;
        if($GLOBALS[uid]<=0){unset($content);$this->html->putLogin($content);exit;}
        return $this->dispatch($what, __FUNCTION__);
    }
    public function pdf($what)
    {
        global $access;
        if($GLOBALS[uid]<=0){unset($content);$this->html->putLogin($content);exit;}
        return $this->dispatch($what, __FUNCTION__);
    }
    public function doc($what)
    {
        global $access;
        if($GLOBALS[uid]<=0){unset($content);$this->html->putLogin($content);exit;}
        return $this->dispatch($what, __FUNCTION__);
    }
    public function json($what)
    {
        global $access;
        if($GLOBALS[uid]<=0){unset($content);$this->html->putLogin($content);exit;}
        return $this->dispatch($what, __FUNCTION__);
    }
    public function api($what)
    {
        global $access;
        return $this->dispatch($what, __FUNCTION__);
    }
    public function graphdata($what)
    {
        global $access;
        if($GLOBALS[uid]<=0){unset($content);$this->html->putLogin($content);exit;}
        return $this->dispatch($what, __FUNCTION__);
    }
    public function tools($what)
    {
        global $access;
        if($GLOBALS[uid]<=0){unset($content);$this->html->putLogin($content);exit;}
        return $this->dispatch($what, __FUNCTION__);
    }
    public function test($what)
    {
        global $access;
        if($GLOBALS[uid]<=0){unset($content);$this->html->putLogin($content);exit;}
        return $this->dispatch($what, __FUNCTION__);
    }

    function shoppingcart()
    {
        return $this->data->shoppingcart();
    }

    private function dispatch($what = '', $function = '', $accessitemchk = '')
    {
        global $limit, $orgqry, $access,$uid,$gid,$reflink,$maxdescr,$today,$ip;
        //if($GLOBALS[uid]<0)return $this->html->error("No access to $function $what ".$GLOBALS[uid]);
        if ($GLOBALS[action_folder]=='') {
            $GLOBALS[action_folder]=$function;
        }
        if ($maxdescr==0) {
            $maxdescr=40;
        }
        $totals=array_fill(0, 50, "");
        $qry=$orgqry;
        $act=$this->html->readRQ('act');
        $page=$this->html->readRQn('page');
        $id=$this->html->readRQn('id');
        $refid=$this->html->readRQn('refid');
        $table=$this->html->readRQ('table');
        $dynamic=$this->html->readRQ('dynamic');

        $notitle=$this->html->readRQn("notitle");
        $noadd=$this->html->readRQn("aoadd");
        $hide_title=$this->html->readRQn("hide_title");

        $nopager=$this->html->readRQn("nopager");
        $noexport=$this->html->readRQn("noexport");
        if (($nopager<>'')) {
            $limit=$GLOBALS[max_rows];
        }
        if (($this->html->readRQn('limit')>0)) {
            $limit=$this->html->readRQn('limit');
        }
        $offset = $limit*$page;
        $n=$offset;
        $sortby=$this->html->readRQ('sortby');
        $opt=$this->html->readRQ('opt');
        $qry=explode("&sortby=", $qry);
        $sorting=$qry[1];
        $qry=$qry[0];
        if (strpos($sorting, "+desc")>0) {
            $order="+asc";
        } else {
            $order="+desc";
        }
        if ($noadd=="") {
            $addbutton=$this->html->add_button($what);
        }

        if ($function=='show') {
            if ($opt!='secondtime') {
                $title=$this->html->readRQ('title');
                if ($title=='') {
                    $title=$what;
                }
                $titleorig=ucfirst($title);

                $title="$addbutton". ucfirst(str_replace('_', ' ', $title)); //remove on new design
                //echo "$what,$function,$accessitemchk,$title,$titleorig";exit;
                //if($what=='documents')$srchbtn="<a href='?act=search&what=$what' class='c'><i class='icon-search'></i></a>";
                if (($notitle=="")&&($hide_title=='')) {
                    $body.= "<h3 class='foldered'>$srchbtn".ucfirst($title)."</h3>\n";
                }
            }
        }

        $procedure_init_file=FW_DIR.DS.'actions'.DS.$function.DS.'_init.php';
        if (file_exists($procedure_init_file)) {
            require $procedure_init_file;
        }
        $procedure_init_file=APP_DIR.DS.'actions'.DS.$function.DS.'_init.php';
        if (file_exists($procedure_init_file)) {
            require $procedure_init_file;
        }
        $procedure_file=APP_DIR.DS.'actions'.DS.$function.DS.strtolower(str_replace("\\", "/", $what)). '.php';
        if (file_exists($procedure_file)) {
            require $procedure_file;
        } else {
            $procedure_file=FW_DIR.DS.'actions'.DS.$function.DS.strtolower(str_replace("\\", "/", $what)). '.php';
            if (file_exists($procedure_file)) {
                require $procedure_file;
            } else {
                $procedure_file=APP_DIR.DS.'actions'.DS.$function.DS.'_default.php';
                if (file_exists($procedure_file)) {
                    require $procedure_file;
                } else {
                    $procedure_file=FW_DIR.DS.'actions'.DS.$function.DS.'_default.php';
                    if (file_exists($procedure_file)) {
                        require $procedure_file;
                    } else {
                        //$body.=$this->html->pre_display('Missing the '.$function.' default file', 'IS error', 'red');
                        $body.=$this->html->message('Missing the '.$function.' default file', 'BI error', 'alert-error');
                    }
                }
            }
        }
        $procedure_out_file=APP_DIR.DS.'actions'.DS.$function.DS.'_out.php';
        if (file_exists($procedure_out_file)) {
            require $procedure_out_file;
        }

        $procedure_out_file=FW_DIR.DS.'actions'.DS.$function.DS.'_out.php';
        if (file_exists($procedure_out_file)) {
            require $procedure_out_file;
        }

        //$access['view_debug']=1;
        if (($accessitemchk!='')&&($access['view_debug'])&&($_GET[plain]=='')) {
            $body.=  "<span media='print' class='noPrint'><a href='?act=details&what=groupaccess&type=$accessitemchk'><span class='btn btn-nano btn-danger'>ACL $accessitemchk</span></a></span>";
        }
        if (($this->data->field_exists($what, 'id'))&&(!($what=='ownership'))&&($id>0)&&(in_array($function, array('details','add','edit','delete','form','save')))) {
            if ($access['view_debug']) {
                $body.= "<span media='print' class='noPrint'>";
                //$body.= "<div class='span12'>".$this->html->array_display2_textarea($this->data->sql_to_array("SELECT * from $what where id=$id")[0], "DATA:$what($id)")."</div>";
                $body.= "<div class='span12'>".$this->html->array_display2D($this->data->sql_to_array("SELECT * from $what where id=$id")[0], "DATA:$what($id)")."</div>";
                $body.= "</span>";
            }

            $this->db->GetVal("insert into tableaccess (tablename,userid,date,time,refid,ip,descr)values('$what',$uid,now(),now(),$id,'$ip','F:$function')");
        }

        return $body;
    }


    function show_docs2obj($ref_id=1, $ref_table='')
    {
        $count=$this->db->GetVal("select count(*) from docs2obj where ref_id=$ref_id and ref_table='$ref_table'")*1;
        if ($count>0) {
            unset($_POST);
            $_POST[noadd]=1;
            $_POST[ref_table]=$ref_table;
            $_POST[ref_id]=$ref_id;
            $_POST[title]="Documents";
            $_POST[noexport]=1;
            return $this->show('documents');
        }
    }
    function show_listitems2obj($ref_id=0, $ref_table='', $title = 'Items')
    {
        $count=$this->db->GetVal("select count(*) from listitem2obj where ref_id=$ref_id and ref_table='$ref_table'")*1;
        if ($count>0) {
            unset($_POST);
            $_POST[noadd]=1;
            $_POST[ref_table]=$ref_table;
            $_POST[ref_id]=$ref_id;
            $_POST[title]=$title;
            $_POST[noexport]=1;
            return $this->show('listitems');
        }
    }

    public function livestatus($html='')
    {
        if(($GLOBALS[offline_mode])||($GLOBALS[pdf_mode])){
            $GLOBALS[offline_messages][]=strip_tags($html);
        }else{
            echo '<script>$("#livestatus").html("'.$html.'");</script>'."\n";
            ob_flush();
            flush();
        }
        return true;
    }

    function pdf_draw_line($y, $thickness = 0.1)
    {
        global $p,$leading,$left,$right,$llx,$urx,$lly,$pageheight,$pagewidth;
        /* Draw lines*/
                $p->setlinewidth($thickness);
                $p->save();
                $p->moveto($llx, $y);
                $p->lineto($urx, $y);
                $p->stroke();
                $p->restore();
    }
    function nf($number){
        return number_format($number, 2, '.', "'");
    }

    public function progress($start_time=0, $rows=1, $i=1, $text='')
    {
        if(($GLOBALS[offline_mode])||($GLOBALS[pdf_mode])){
            # $GLOBALS[offline_messages][]=strip_tags($html);
        }else{
            if($start_time==0){
                if($GLOBALS[start_time]*1==0){
                    $GLOBALS[start_time]=$this->utils->get_microtime();
                }
                $start_time=$GLOBALS[start_time];
            }
            $lapse_time=$this->utils->get_microtime()-$start_time;
            $time_left=round(($lapse_time/$i)*($rows-$i), 0);
            $t_fract=ceil(($rows-$i)/($time_left/1));
            $lapse_time=round($lapse_time, 2);
            if ($t_fract>$GLOBALS[progress_delay]) {
                $GLOBALS[progress_delay]=$t_fract;
            }
            if (($GLOBALS[progress_delay]==INF)||($GLOBALS[progress_delay]==NAN)||($GLOBALS[progress_delay]==0)||($i<2)) {
                $GLOBALS[progress_delay]=1;
                return true;
            }
            if (!($i % $GLOBALS[progress_delay])) {
                $this->livestatus("$text<br>".str_replace("\"", "'", $this->html->draw_progress($i/$rows*100))."TL:$lapse_time secs.: TTE: $time_left secs. ($GLOBALS[progress_delay])");
            }
            //if($lapse_time>600){die("<br>Running over $lapse_time seconds. Terminated.<br> or use [\$start_time=\$this->utils->get_microtime();]");}
        }
        return true;
    }
}
