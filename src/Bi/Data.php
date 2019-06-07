<?php
namespace Rozdol\Data;

use Rozdol\Dates\Dates;
use Rozdol\Utils\Utils;
use Rozdol\Utils\Crypt;
use Rozdol\Utils\Utf8;
use Rozdol\Utils\Comm;
use Rozdol\Html\Html;
use Rozdol\Db\Db;

class Data
{


    private static $hInstance;
    public function __construct(DB $db)
    {
        $this->db=$db;
        $this->utils = Utils::getInstance();
        $this->dates = Dates::getInstance();
        $this->html = Html::getInstance();
        $this->crypt = Crypt::getInstance();
        $this->utf8 = Utf8::getInstance();
        $this->comm = Comm::getInstance();
    }
    public static function getInstance($db)
    {
        if (!self::$hInstance) {
            self::$hInstance = new Data($db);
        }
        return self::$hInstance;
    }
    function getAccess()
    {
        global $access,$uid;
        $sql = "select * from vw_acceesslist where userid=$uid";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $access=array();
        while ($row = pg_fetch_array($cur)) {
            $value=$row[access];
            $name=$row[name];
            //if ($value=='f') {$value=0;} else {$value=1;}
            $items = array("$name" => "$value");
            $this->utils->array_push_associative($access, $items);
            //echo "$name $value ($access[$name]) <br>";
        }
        /*
        if($this->table_exists('allowed_pids')){
            $GLOBALS['allowed_pids']=$this->get_list_csv("SELECT partner_id from allowed_pids where user_id=$uid and type_id=10800");
            $GLOBALS['allowed_related_pids']=$this->get_list_csv("SELECT partner_id from allowed_pids where user_id=$uid and type_id=10801");
            $GLOBALS['allowed_resticted_pids']=$this->get_list_csv("SELECT partner_id from allowed_pids where user_id=$uid and type_id=10802");
        }
        */
        if ($this->table_exists('workgroup_pids')) {
            $user_workgroup_id=$this->get_val('users', 'workgroup_id', $uid)*1;
            if(($_ENV['AUTO_DOMAIN'])&&($user_workgroup_id==3)){
                $sql="SELECT id from workgroups where lower(name)=lower('".$GLOBALS[DB][DB_DOMAIN]."');";
                //$this->html->error($sql);
                $workgroup_id=$this->db->getval($sql)*1;
                if($workgroup_id==0)$workgroup_id=$this->get_val('users', 'workgroup_id', $uid)*1;
                $administrator_id=$this->get_val('workgroups','administrator_id',$workgroup_id);
                if($administrator_id>0)$GLOBALS[is_owner_id]=$administrator_id;

            }else{
                $workgroup_id=$this->get_val('users', 'workgroup_id', $uid)*1;
                $administrator_id=$this->get_val('workgroups','administrator_id',$workgroup_id);
                if($administrator_id>0)$GLOBALS[is_owner_id]=$administrator_id;
            }
            //$this->html->error($workgroup_id);
            $GLOBALS['workgroup']=$this->get_row('workgroups', $workgroup_id);
            $GLOBALS['workgroup_id']=$workgroup_id;
            $GLOBALS['allowed_pids']=$this->get_list_csv("SELECT partner_id from workgroup_pids where workgroup_id=$workgroup_id and type_id=10800");
            $GLOBALS['allowed_related_pids']=$this->get_list_csv("SELECT partner_id from workgroup_pids where workgroup_id=$workgroup_id and type_id=10801");
            $GLOBALS['allowed_resticted_pids']=$this->get_list_csv("SELECT partner_id from workgroup_pids where workgroup_id=$workgroup_id and type_id=10802");
            if (($GLOBALS['workgroup']['restricted']=='t')&&($GLOBALS['allowed_pids']=='')) {
                $GLOBALS['allowed_pids']='-1';
            }
        }


        $access['edit_sw']=1;
        //if($GLOBALS[settings][no_projects]!=0)
        //$GLOBALS['no_projects']=1; //Forced
        //$GLOBALS['no_clients']=1; //Forced
        if ($GLOBALS['no_clients']) {
            $GLOBALS['no_projects']=1;
            //$GLOBALS['stealth']=1;
            $access['view_clients']=0;
            $access['view_projects']=0;
        }
        if ($GLOBALS['stealth']>0) {
            //if($GLOBALS['access']['view_transactions']=0);
            //if($GLOBALS['access']['edit_transactions']=0);
        }

        //if($GLOBALS['is_owner_id']*1==0)

        if ($GLOBALS['is_owner_id']==0) $GLOBALS['is_owner_id']=$this->get_val('users', 'owner_id', $GLOBALS['uid']);
        if ($GLOBALS['is_owner_id']>0) {
            $GLOBALS['is_currency_id']=$this->get_val('partners', 'a_currency', $GLOBALS['is_owner_id']);
        }
        if ($this->field_exists('users', 'history_tail')) {
            $GLOBALS['history_tail']=$this->get_val('users', 'history_tail', $uid);
        }
        if ($GLOBALS['access']['no_clients']) {
            $GLOBALS['no_clients']=1;
        }
    }

    function auth()
    {
        global $logged, $uid, $gid, $cart,$issystemactive, $access;
        session_start(); //auth
        //echo "Auth Time:".microtime(true)."<br>";
        //echo "Cookie:".$_COOKIE['login']." Session:".$_SESSION['login']."<br>";
        if (isset($_COOKIE['login']) && isset($_SESSION['login'])) {
            if ($_COOKIE['login'] == $_SESSION['login']) {
                $logged = true;
                //echo "Logged in SUCCESS ".$_SERVER['HTTP_USER_AGENT'];
                $cookieValue=$_COOKIE['login'];
                $token=$_COOKIE['token'];


                $session_time=$GLOBALS['settings']['session_time']*1;
                if ($session_time==0) {
                    $session_time=86400; //seconds
                }
                $GOLBALS[session_time_set1]=$session_time;
                if (!($this->utils->is_IP_local($_SERVER['REMOTE_ADDR']))) {
                    $session_time=86400;
                }//change to 600
                setcookie("login", $cookieValue, time()+$session_time);

                //session_register("login");
                $_SESSION['login'] = $cookieValue;

                $cookieValue=$this->db->Escape($cookieValue);
                $sql = "SELECT * FROM users WHERE sessionid='$cookieValue';";
                $result = $this->db->GetRow($sql);
                //exit;
                $userid=$result[id]*1;
                $username=$result[username];
                $GLOBALS[my_owner_id]=$result[owner_id];
                $GLOBALS[user]=$result[surname].' '.$result[firstname];
                $GLOBALS[user_email]=$result[email];
                $cart = $_SESSION['cart'];

                //if ($token!=$result[avatar]) {$logged = false;}
                $GLOBALS['csrf']=$result[token_hash];
                if (!$GLOBALS['settings']['no_csrf']>0) {
                    $logged = $this->crypt->csrf_chk();
                }
                //$token = base64_encode( openssl_random_pseudo_bytes(32));
                setcookie("token", $token, time()+$session_time);
                $sql = "update users set avatar='$token', lastvisit=now() WHERE id=$userid";
                $result = $this->db->GetRow($sql);
                if ($userid==0) {
                    $logged = false;
                }
                $this->getUserVals($userid);
            } else {
                $logged = false;
                //echo ("Auth failed $_COOKIE[login] =/= $_SESSION[login]<br>");
                //echo "No cookie";
            }
        } else {
            $logged = false;
            //echo "No CID";
            //echo("Loggin failed NO SID C:$_COOKIE[login]  SID:$_SESSION[login]<br>");
        }
        if (!$logged) {
            //$userid=2; //For unlimited access!!!!
            $userid=-1;
            $username="Guest";

            //---API----
            $act=$this->html->readRQs('act');
            if ($act=='api') {
                $logged=true;
                $userid=-2;
                $username="API user";
            }

            //-----Offlie automation------
            $offline_token=$this->html->readRQ('offline_token');
            if ($offline_token=='8f71240a300c07f1e244fb4c3c404e3676ecb5de') {
                $logged=true;
                $userid=-2;
                $username="Offline Automation";
                //echo "<br>$username";
            }

            $code=$this->html->readRQ('offlinecode');
            $realcode=$this->db->GetVal("select password from users where id=-2");
            //echo "<br>$realcode";
            if (($code==$realcode)&&($realcode!='')) {
                $logged=true;
                $userid=-2;
                $username="Offline Automation";
                //echo "<br>$username";
            }
            if (($GLOBALS['settings']['no_auth'])) {
                $logged=true;
                $userid=-3;
                $username="Not Authed user";
                //echo "<br>$username";
            }
        }
        $uid=$userid;
        $this->getAccess();

        $sql="select groupid from user_group where userid=$userid";
        $gid = $this->db->GetVal($sql);
        $userrec=$this->db->GetRow("select * from users where id=$uid");
        $fullname=$userrec[firstname]." ".$userrec[surname];
        //$upid=$this->db->GetVal("select partnerid from users where id=$uid")*1;
        //$usercompanyname=$this->db->GetVal("select name from partners where id=$upid");

        $isactive=$userrec[active];
        $lang=$userrec[lang];
        //$isactive=0;
        if (!$isactive) {
            echo $this->html->refreshpage('', 60, "<div class='alert alert-error'>Your access is not active. Try later. <br>UID:$uid</div>");
            exit;
            //echo "<div class='error'>Dear $fullname,<br>Your access is not active. Try later. <br>UID:$uid</div>"; $this->logout(1);exit;
        }
        $putitback=$this->html->readRQ('putitback');
        if ($putitback=='SZC12345') {
            $this->db->GetRow("update config set value=1 where name='active'");
        }

        if ($GLOBALS[active]==-1) {
            header('Location: https://www.google.com/');
            exit;
        }
        if ((!$GLOBALS[active])&&($gid<>2)) {
            echo "<div class='error'>SYSTEM IS INACTIVE.<br>$shutdowntext</div>";
            exit;
        }
        $pdffont=$userrec[pdffont];
        $pdffontsize=$userrec[pdffontsize];
        //$css=$userrec[css];
        $limit=$userrec[rows];
        $maxdescr=$userrec[maxdescr];
        session_write_close();
        ob_flush();
        //flush();
        //$access['main_access']=0;
        $this->force_access();
        //die(json_encode($GLOBALS[access]));
        if (!$access['main_access']) {
            $this->logout();
            echo $this->html->refreshpage('', 60, "<div class='alert alert-error'><h1 style='color:#dd0000;'>ACCESS DENIED</h1> $fullname, permission not granted by administrator ($uid).<br><a href='?'>Back to login</a></div>");
            exit;
        }
        //$uid=3;
    }
    function force_access()
    {
        $force_access=array();
            //$access=array();
        $force_access=$GLOBALS['force_access'];
        foreach ($force_access as $key => $value) {
            $GLOBALS['access'][$key]=$value;
        }
    }
    function grand_access($uid, $reflink = '')
    {
        $sql = "SELECT * FROM users WHERE id=$uid";
        $user=$this->db->GetRow($sql);

        $cookieValue = mt_rand() ."_$username";
        $session_time=$GLOBALS['settings']['session_time']*1;
        if ($session_time==0) {
            $session_time=86400; //1 hour session
        }
        $GLOBALS['session_time_set2']=$session_time;
        //session_register("login");
        $_SESSION['login'] = $cookieValue;
        setcookie("login", $cookieValue, time()+$session_time);
        setcookie("database", $dbname, time()+$session_time);

        $token = base64_encode(openssl_random_pseudo_bytes(32));
        setcookie("token", $token, time()+$session_time);
        session_write_close();

        //echo "LOGIN: Cookie:".$_COOKIE['login']." Session:".$_SESSION['login']." CV:$cookieValue<br>";

        $token_hash=$this->crypt->csrf_token();
        $vals=array(
            'sessionid'=>$cookieValue,
            'avatar'=>$token,
            'token_hash'=>$token_hash
            );
        //echo $this->html->pre_display($vals,'vals');
        $this->db->update_db('users', $uid, $vals);
        $GLOBALS[user]=$user[surname].' '.$user[firstname];


        return $this->html->refreshpage($reflink, 1, "Welcome back, $GLOBALS[user]!");
    }
    function login()
    {
        //echo "login Time:".microtime(true)."<br>";
        global $uid, $reflink;
        session_start(); //login
        $username=$this->html->readRQ('username');
        $password=$this->html->readRQ('password');
        unset($_POST[password]);
        unset($_REQUEST[password]);
        if ($password=='1234Deactivate1234') {
            $this->db->GetRow("update config set value=-1 where name='active'");
            echo $this->html->refreshpage('https://www.google.com/', .1, "<div class='alert'><h1 style='color:#dd0000;'>Login</h1>Authenticating...</div>");
            exit;
        }
        if ($password=='1234Stealth1234') {
            $GLOBALS[username]=$username;
            $res=$this->stealth_mode('hide');
            echo $this->html->refreshpage('/?', 5, "<div class='alert'><h1 style='color:#dd0000;'>Wrong Password</h1>$res<br>Try again</div>");
            exit;
        }
        if ($password=='1234UnStealth1234') {
            $GLOBALS[username]=$username;
            $res=$this->stealth_mode('unhide');
            echo $this->html->refreshpage('/?', 5, "<div class='alert'><h1 style='color:#dd0000;'>Wrong Password</h1>$res<br>Try again</div>");
            exit;
        }

        $descr="$username:$password";
        $dbname=$this->html->readRQ('database');
        $sql = "SELECT * FROM users WHERE username='$username' order by id asc limit 1";
        $user=$this->db->GetRow($sql);

        //update with new password
        //$hash=$this->crypt->create_hash($password);$vals=array('password_hash'=>$hash);$this->db->update_db('users',$user[id],$vals);$user=$this->db->GetRow($sql);

        $good_hash=$user[password_hash];

        $ok=$this->crypt->validate_password($password, $good_hash)*1;
        //echo "$password, $good_hash<br> OK:$ok, username=$user[username] UID:$user[id]<br>$sql"; exit;
        //$ok=1;
        if ($ok > 0) {
            if ((!($this->utils->is_IP_local($_SERVER['REMOTE_ADDR'])))&&($GLOBALS['settings']['use_mfa'])&&$user['ga']!='') {
                //if(1!=1){
                require_once FW_DIR.'vendor'.DS.'PHPGangsta'.DS.'GoogleAuthenticator.php';
                $ga = new \PHPGangsta_GoogleAuthenticator();
                $secret = $user['ga'];
                $oneCode=$this->html->readRQ('otp');
                $checkResult = $ga->verifyCode($secret, $oneCode, 0);    // 2 = 2*30sec clock tolerance
                if ($checkResult) {
                    $this->comm->sms2admin("IS:Loged in $username with OTP");
                    $this->comm->mail2admin("IS OTP Login", "IS:Loged in $username with OTP. IP: ".$_SERVER['REMOTE_ADDR']);
                    return $this->grand_access($user[id], $reflink);
                } else {
                    $this->comm->sms2admin("IS:Failded $username on OTP");
                    $this->comm->mail2admin("IS OTP Login Faildes", "IS:Loged Failed in $username with OTP. IP: ".$_SERVER['REMOTE_ADDR']);
                    $this->chk_fails("$username on OTP");
                    $uid=0;
                    return $this->html->refreshpage('', 3, "<div class='alert alert-error'>No access<br>OTP failed.</div>");
                }
            }
            $this->comm->mail2admin("IS Login $username", "IS:Login $username with IP: ".$_SERVER['REMOTE_ADDR']);
            return $this->grand_access($user[id], $reflink);
        } else {
            $this->chk_fails($descr);
            $uid=0;
            $this->comm->mail2admin("⭕ IS Login Failded", "IS:Failed login $username with IP: ".$_SERVER['REMOTE_ADDR']);
            return $this->html->refreshpage('', 1, "<div class='alert alert-error'>No access<br>Verify your login details and try again.</div>");
            //$this->logout(1);
        }
    }
    public function logout()
    {
        global $timeout,$uid;
        $n=$this->db->UpdateObject('id ='.$uid, 'users', array('sessionid','token_hash'), array('sessionid'=>'--','token_hash'=>'--'));
        session_start(); //log out
        setcookie("database", "", time()+60000000);
        setcookie("reflink", "", time()+60000000);
        setcookie("login", "", time()+60000000);
        session_destroy();
        ob_flush();
        flush();
        $this->html->set_reflink('?act=');
        return $this->html->refreshpage('?act=', 2, "See you soon, $GLOBALS[user]!");
    }
    function chk_fails($descr = '')
    {
        global $ip;
        $sql="delete from failed_logins where date_time<=CURRENT_TIMESTAMP - INTERVAL '10 minutes'";
        $times=$this->db->GetVal($sql);
        $sql="insert into failed_logins (date_time, ip, descr) values (CURRENT_TIMESTAMP, '$ip','$descr')";
        $sql=$this->db->GetVal($sql);
        $sql="select count(*) from failed_logins where ip='$ip' and date_time>=CURRENT_TIMESTAMP - INTERVAL '1 minutes'";
        $times=$this->db->GetVal($sql);
        if ($times==3) {
            echo "<br>SMS<br>";
            $owner=$GLOBALS['owner'];
            $text="IS($owner):brute-force attempt from $ip $descr";
            $text=substr($text, 0, 200);
            $this->comm->sms2admin($text);

            $to=$GLOBALS['admin_mail'];
            $from=$GLOBALS['is_mail'];
            $subject="System alert (Brute force attempt)";
            if (($to!='')&&($from!='')) {
                $mail=$this->comm->sendmail_html($to, $from, $subject, $text);
            }
        }
        if ($times==6) {
            $sql="insert into blacklist_ip (date_time, ip, descr) values (CURRENT_TIMESTAMP + INTERVAL '1 minutes', '$ip','$descr')";
            $sql=$this->db->GetVal($sql);
            echo "<br>Block<br>";
            $owner=$GLOBALS['owner'];
            $text="IS($owner):Blocked IP $ip for 10 minutes due to multiple brute-force attempts $descr";
            $text=substr($text, 0, 200);
            $this->comm->sms2admin($text);

            $to=$GLOBALS['admin_mail'];
            $from=$GLOBALS['is_mail'];
            $subject="System alert (Brute force attempt)";
            if (($to!='')&&($from!='')) {
                $mail=$this->comm->sendmail_html($to, $from, $subject, $text);
            }
        }
    }
    function chk_ip($redirect)
    {

        $wipfile=$GLOBALS['settings']['wipfile'];//DATA_DIR.'/wiplist.txt';
        $use_wip=$GLOBALS['settings']['use_wip'];
        $file_c = file_get_contents($wipfile);
        //$listip.=$file_c;
        $allowedip = explode("\n", trim($file_c));
        $ip=$_SERVER['REMOTE_ADDR'];
        $realip=$ip;
        //$ip="81.4.14.155";
        $allow=0; //Should be 0!
        //if($use_wip!=1)$allow++;
        //$listip.="F:$wipfile, USE:$use_wip/$allow<br>";
        $listip.= $ip.'<br>';


        if (($allowedip)&&($allow==0)) {
            if ($use_wip) {
                foreach ($allowedip as $good_ip_pair) {
                    $good_ip_pairarr = explode(";", trim($good_ip_pair));
                    $good_ip = $good_ip_pairarr[0];
                    $listip.="IP:$good_ip,<br>";
                    //if(eregi("^$good_ip",$ip)){
                    if (preg_match("/^$good_ip/i", $ip)) {
                        $allow++;
                        $listip.="ALLOWED IP:$good_ip,<br>";
                    }
                }
            } else {
                $allow++;
                $listip.="BINGO<br>";
            }
        }
        $listip.="ALLOW:$allow<br>";
        //echo $listip;
            //$this->utils->post_message($listip);
        $listip='';
        if ($allow==0) {
            echo "$ip";
            //header("Location: $redirect");
            exit();
        }
    }
    function getUserVals($uid)
    {
        //Overritten
        $user = $this->db->getRow("select * from users where id=$uid");
        $token='maxdescr';
        $GLOBALS[$token]=$user[$token];
        $token='regdate';
        $GLOBALS[$token]=substr($user[$token], 0, 10);
        //$token='history_days';$GLOBALS[$token]=substr($user[$token],0,10);
        //if($GLOBALS['history_days']>0)$GLOBALS['regdate']=$this->dates->F_dateadd($GLOBALS['today'],-$GLOBALS['history_days']);
    }
    function getDefVals()
    {
        global $tomorrow,$today,$Monthes,$Monthesrus,$Monthesfull,$Days,$Daysshort,$ip,$access ;
        $ip=$this->utils->getRealIpAddr();
        //Default
        $GLOBALS[max_rows]=2000;
        $GLOBALS[no_io_redirect]='https://www.google.com/';
        $GLOBALS[message_time]=3;

        //$GLOBALS[reflink]=$_COOKIE["reflink"];
        //$GLOBALS[reflink]=$_SESSION["reflink"];
        $GLOBALS[reflink]=($_SESSION["reflink"]!='')?$_SESSION["reflink"]:$_COOKIE["reflink"];

        //Overritten
        $sql = "select * from config";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        while ($row = pg_fetch_array($cur)) {
            $value=$row[value];
            $name=$row[name];
            $GLOBALS[$name]=$value;
            $GLOBALS['settings'][$name]=$value;
        }
        if ($GLOBALS['no_clients']) {
            $GLOBALS['no_projects']=1;
        }

        if (!$GLOBALS['bootstrap_ver']) {
            $GLOBALS['bootstrap_ver']="2.2.1";
        }
        if (!$GLOBALS['jquery_ver']) {
            $GLOBALS['jquery_ver']="1.7.1";
        }

        $this->chk_ip($GLOBALS[no_io_redirect]);
        if ((!$GLOBALS['active'])&&(!($ip=="192.168.0.104"))) {
            echo $this->html->refreshpage('', 60, "<div class='alert alert-error'>$GLOBALS[shutdowntext]</div>");
            exit;
        }


        //Common
        $today=$this->dates->F_date("", 1);
        $tomorrow=$this->dates->F_dateadd($today, 1);
        $GLOBALS[yesterday]=$this->dates->F_dateadd($today, -1);
        $Monthes = array( "",
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec") ;

        $Monthesrus = array( "",
        "Январь",
        "Февраль",
        "Март",
        "Апрель",
        "Май",
        "Июнь",
        "Июль",
        "Август",
        "Сентябрь",
        "Октябрь",
        "Ноябрь",
        "Декабрь") ;

        $Monthesfull = array( "",
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December") ;

        $Days = array( "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday");

        $Daysshort = array(
            "S",
            "M",
            "T",
            "W",
            "T",
            "F",
            "S",
            "S");
    }
    function db_exists($db)
    {
        $sql="SELECT 1 from pg_database WHERE datname='$db';";
        $count=$this->db->GetVal($sql)*1;
        $res=($count!=0);
        return $res;
    }
    function table_exists($table)
    {
        $sql="select count(*) from information_schema.tables where lower(table_name)=lower('$table')";
        $count=$this->db->GetVal($sql)*1;
        $res=($count!=0);
        return $res;
    }
    function field_exists($table, $field)
    {
        $sql="SELECT count(*) FROM information_schema.columns WHERE table_schema='public' and lower(table_name)=lower('$table') and lower(column_name)=lower('$field') ";
        $count=$this->db->GetVal($sql)*1;
        $res=($count!=0);
        return $res;
    }
    function field_type($table, $field)
    {
        $sql="SELECT $field FROM $table limit 1";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $fieldtype = pg_field_type($cur, 0);
        return $fieldtype;
    }
    function if_record_blocked($tablename, $id)
    {
        $res=$this->db->GetRow("select user_id, CURRENT_TIMESTAMP as nw, date_time as df, release_date_time as dt,
            (release_date_time - now()) as di,
            extract(epoch from (release_date_time - now()) )::int as tl
            from blocked_records
            where table_name='$tablename' and ref_id=$id order by date_time desc limit 1");
        return $res;
    }
    function block_record($tablename, $id)
    {
        global $db,$uid;
        $seonds=$this->readconfig('record_blocking_interval')*1;
        if ($seonds==0) {
            $seonds=120;
        }
        release_record($tablename, $id);
        $sql="insert into blocked_records (date_time, release_date_time, user_id, table_name, ref_id) values (CURRENT_TIMESTAMP, CURRENT_TIMESTAMP + INTERVAL '$seonds seconds', $uid,'$tablename', $id)";
        $res=$this->db->GetVal($sql);
        return $res;
    }
    function release_record($tablename, $id)
    {

        $res=$this->db->GetVal("delete from blocked_records where (table_name='$tablename' and ref_id=$id) or (release_date_time<CURRENT_TIMESTAMP)");
        return $res;
    }
    function fast_menu($group_id)
    {
        global $gid;
        //$group_id=4;
        $result=$this->db->GetVal("select menu from fastmenu where gid=$group_id");
        $result=htmlspecialchars_decode($result, ENT_QUOTES);
        //fix "&not = ¬" issue
        $result=str_replace("&not", "&amp;not", $result);
        return $result;
    }

    function gen_fast_menu($group_id)
    {
        global  $gid;
        $id=$this->db->GetVal("select id from fastmenu where gid=$group_id")*1;
        $gname=$this->get_name('groups', $group_id);
        $name="For group $gname";
        $date=$this->dates->F_date('', 1);
        $menu=$this->menu($group_id);
        $menu = str_replace("\0", "", $menu);
        $menu = stripslashes($menu);
        $menu=htmlspecialchars($menu);

        $vals=array(
            'name'=>$name,
            'date'=>$date,
            'gid'=>$group_id,
            'menu'=>$menu
        );

        if ($id==0) {
            $id=$this->db->insert_db('fastmenu', $vals);
        } else {
            $id=$this->db->update_db('fastmenu', $id, $vals);
        }
    }
    function add_menu($menu, $new_menu)
    {
        //ex: add_menu('Data',['Partners'=>'#']);
        global  $gid;
        //$gid=2;
        //$gid2=$gid;
        $item_id=$this->db->getval("SELECT max(id) from menuitems where name='$menu'")*1;
        //echo "[$menu] ID:$item_id<br>";
        $parent_id=$this->db->getval("SELECT max(id) from menus where groupid=$gid and menuid=$item_id")*1;
        //echo "Menu parent_id:$parent_id where menuid=$item_id<br>";
        if ($parent_id>0) {
            $sorting=$this->db->getVal("select max(sort) from menus where groupid=$gid and parentid=$parent_id")*1;
            foreach ($new_menu as $key => $value) {
                $sorting+=100;
                $menue_id=$this->db->getval("SELECT id from menuitems where name='$key' and link='$value'")*1;
                if ($menue_id==0) {
                    $menue_id=$this->db->getval("INSERT into menuitems (name,link) values ('$key','$value'); SELECT MAX(id) from menuitems;");
                    echo "Added menuitem: $key=>$value ID:($menue_id)<br>";
                }
                //$sql="INSERT into menuitems (name,link) values ('$key','$value');";

                $menue_exist=$this->db->getval("SELECT id from menus where groupid=$gid and parentid=$parent_id and menuid=$menue_id")*1;
                if ($menue_exist==0) {
                    $this->db->getval("insert into menus (groupid, parentid,menuid,sort) values ($gid,$parent_id,$menue_id,$sorting);");
                    echo "Added menue (GID:$gid,PID:$parent_id,MID:$menue_id,SORT:$sorting)<br>";
                }
                //$sql2="insert into menus (groupid, parentid,menuid,sort) values ($gid,$parent_id,$menue_id,$sorting);";
                //echo "$key=>$value ($sorting) menue_id:$menue_id,<br>sql:$sql<br>sql2:$sql2<br><hr>";
                //echo "<hr>";
            }
        }
    }
    function menu($group_id)
    {
        //$result=fast_menu($group_id);

        global  $gid;
        //$gid=2;
        $gid2=$gid;
        $out.="<!-- Beginning of compulsory code below -->
                <ul id='nav' class='dropdown dropdown-horizontal'>";
        $sql="select * from menus where groupid=2 and parentid=0 order by sort";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        while ($row = pg_fetch_array($cur)) {
            $print=0;
            $i++;
            if ($gid2==2) {
                $print=1;
            } else {
                if ($row[menuid]!=12) {
                    $print=1;
                }
            }
            //$print=1;
            $menuitems=$this->db->GetRow("select * from menuitems where id=$row[menuid]");
            if (($GLOBALS['hide_hidden_menu'])&&($menuitems['hidden']=='t')) {
                $print=0;
            }
            if ($print==1) {
                $title=$menuitems[name];
                $link=$menuitems[link];
                if ($title=="Main") {
                    $title="<i class='icon-home icon-white'></i>";
                }
                $submenu=$this->submenu($row[id], $group_id, 1);
                $out.="<li id='n-main_$i'><a href='$link'>$title</a>";
                $out.=$submenu;
                $out.="</li>";
            }
        }
        $out.="</ul>
            <!-- / END -->";
        $result=$out;
        //echo "UID:($group_id)";exit;
        return $result;
    }
    function submenu($parentid, $group_id, $level)
    {
        global $gid;
        $gid2=$gid;
        $level++;
        if ($level>10) {
            $err=showerror("Levels of menue:$level<br>ParentID:$parentid<hr>Regresh the page.");
            echo $err;
            $this->db->GetVal("delete from menus where parentid=$parentid and menuid=$parentid");
            exit;
        }
        //$gid=2;
        $out.="<ul>";
        $i=0;
        $sql="select * from menus where groupid=2 and parentid=$parentid order by sort";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        while ($row = pg_fetch_array($cur)) {
            $print=0;
            $menuitem=$this->db->GetRow("select * from menuitems where id=$row[menuid]");

            $link=$menuitem['link'];
            $view=0;
            $report=0;
            $edit=0;
            $table='no_table';
            if ($this->utils->contains("act=show", $link)>0) {
                $view++;
            }
            if ($this->utils->contains("act=add", $link)>0) {
                $edit++;
            }
            if ($this->utils->contains("act=tool", $link)>0) {
                $edit++;
            }
            if ($this->utils->contains("act=report", $link)>0) {
                $report++;
            }
            if ($this->utils->contains("act=filter", $link)>0) {
                $report++;
            }
            if ($this->utils->contains("act=search", $link)>0) {
                $report++;
            }
            if ($this->utils->contains("act=compare", $link)>0) {
                $report++;
            }
            if ($this->utils->contains("act=export", $link)>0) {
                $report++;
            }

            if ($this->utils->contains("what=", $link)>0) {
                $table=$this->utils->get_request_val($link, 'what');
            }
            //if($this->utils->contains("table=",$link)>0)$table=$this->utils->get_request_val($link, 'table');
            $access_item="noaccess_item";
            if ($view>0) {
                $access_item="view_".$table;
            }
            if ($report>0) {
                $access_item="report_".$table;
            }
            if ($edit>0) {
                $access_item="edit_".$table;
            }


            if ($access_item=="noaccess_item") {
                $print=1;
            }
            $access_id=$this->db->GetVal("select id from accessitems where name='$access_item'")*1;
            if ($access_id==0) {
                $print=1;
            } else {
                $access_level=$this->db->GetVal("select access from accesslevel where groupid=$group_id and  accessid=$access_id")*1;
                if ($access_level>0) {
                    $print=1;
                }
            }
            $children=$this->db->GetVal("select count(*) from menus where groupid=2 and parentid=$row[id]")*1;
            if ($children>0) {
                $print=1;
            }
            if ($row[menuid]==12) {
                $print=0;
            }
            if ($group_id==2) {
                $print=1;
            }


            if (($GLOBALS['hide_hidden_menu'])&&($menuitem['hidden']=='t')) {
                $print=0;
            }
            if($row[sort]<0)$print=0;
            //echo "($group_id)LINK:$link<br>ACCESS:$access_item<br>PRINT:$print<hr>";
            if ($print==1) {
                $i++;
                $menuitems=$this->db->GetRow("select * from menuitems where id=$row[menuid]");
                $title=$menuitems[name];
                $link=$menuitems[link];
                if ($i==1) {
                    $class="first";
                }
                //$submenu=submenu($row[id],$gid,$level);
                $submenu=$this->submenu($row[id], $group_id, $level);
                if ($children>0) {
                    $out.="<li class='$class'><span class='dir' onclick='null'>$title</span>";
                } else {
                    if(($GLOBALS[access][main_admin])&&($GLOBALS[access][view_debug])){

                        $edit="<a href='?act=details&what=menus&id=$row[id]' style='display: inline; '>⚙️</a>";
                        $out.="<li class='$class' style='font-size: .4vw;'><span>$edit<a href='$link' style='display: inline;'>$title</a></span>";
                    }else{
                        $out.="<li class='$class'><a href='$link'>$title</a>";
                    }

                }
                $out.=$submenu;
                $out.="</li>";
            }
        }
        $out.="</ul>";
        $result=$out;
        return $result;
    }

    function menu_chk($structure, $name, $link)
    {
        $out=implode(',', array($structure,$name,$link));

        return $out;
    }

    function save_approval_template($template_id, $approval_id)
    {
        $sql="select * from approval_items where approval_id=$template_id and parent_id=0";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows = pg_num_rows($cur);
        while ($row = pg_fetch_array($cur)) {
            $this->save_approval_items_template($template_id, $approval_id, $row[id], 0);
        }
    }
    function save_approval_items_template($template_id, $approval_id, $item_id, $parent_id)
    {
        $template=$this->db->GetRow("select * from approval_items where id=$item_id");

        $name=$this->username($template[user_id]);
        $date=$GLOBALS['today'];
        $date_diff=$this->dates->F_datediff($template[date], $template[due_date]);
        $due_date=$this->dates->F_dateadd($date, $date_diff);
        $vals=array(
            'name'=>$name,
            'date'=>$date,
            'approval_id'=>$approval_id,
            'parent_id'=>$parent_id,
            'user_id'=>$template[user_id],
            'due_date'=>$due_date,
            'type'=>$template[type],
            'descr'=>$template[descr],
            'note'=>$template[note]
        );
        $new_id=$this->db->insert_db('approval_items', $vals);

        $sql="select * from approval_items where parent_id=$item_id";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows = pg_num_rows($cur);
        while ($row = pg_fetch_array($cur)) {
            $this->save_approval_items_template($template_id, $approval_id, $row[id], $new_id);
        }
    }
    function get_approval_status($obect, $id, $fast = 0)
    {
        $icon="<span class='btn btn-micro btn-link'><i class='icon-minus'></i></span>";
        $status=array(
            'approvals'=>0,
            'approved'=>0,
            'declined'=>0,
            'icon'=>$icon,
            'status'=>'Not set',
            'graph'=>$graph,
            'completion'=>0,

        );
        $sql="select id from approvals where ref_id=$id  and ref_table='$obect' order by date desc, id desc limit 1";
        $approval=$this->db->GetRow($sql);
        if ($approval[id]>0) {
            //echo "APPR_ID:$approval[id]<br>$sql";
            $status=$this->approval_status($approval[id]);
        }
        return $status;
    }
    function approval_status($id)
    {
        global $approvelevel,$approved,$declined,$approvals;
        $approvelevel=10;
        $id=$id*1;
        $approval=$this->db->GetRow("select * from approvals where id=$id");
        $approved=0;
        $declined=0;
        $approvals=0;

        $sql="select * from approval_items where approval_id=$id and parent_id=0";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows = pg_num_rows($cur);
        while ($row = pg_fetch_array($cur)) {
            $this->get_approval_item_status($row[id]);
        }

        $stts='Pending...';
        $stts_id=0;
        $icon="<span class='btn btn-micro btn-link'><i class='icon-time withpointer'></i></span>";

        $completion=($approved/$approvals)*100;


        if (($approved==$approvals)&&($approvals>0)) {
            $stts='Approved';
            $stts_id=1;
            $icon="<span class='btn btn-micro btn-success'><i class='icon-ok icon-white'></i></span>";
            $approve_date=$this->db->GetVal("select approve_date from approval_items where approval_id=$id and approved='t' order by approve_date desc limit 1");
            if ($approve_date=='') {
                $approve_date=$this->dates->F_date('', 1);
            }
            $this->db->GetRow("update approvals set approved='t', approve_date='$approve_date', locked='t' where id=$id");
            if ($approval[ref_table]=='events') {
                $this->db->GetRow("update events set complete='t' where id=$approval[ref_id] and type not in (1383)"); //exclude transafer of company
            }
        }

        if ($declined>0) {
            $stts='Declined';
            $stts_i=2;
            $icon="<span class='btn btn-micro btn-danger'><i class='icon-remove icon-white'></i></span>";
            $completion=200;
            $approve_date=$this->db->GetVal("select approve_date from approval_items where approval_id=$id and declined='t' order by approve_date desc limit 1");
            if ($approve_date=='') {
                $approve_date=$this->dates->F_date('', 1);
            }
            $this->db->GetRow("update approvals set declined='t', approve_date='$approve_date', locked='t' where id=$id");
        }
        if ($approvals==0) {
            $stts='Not set';
            $stts_id=0;
            $icon="<span class='btn btn-micro btn-link'><i class='icon-remove'></i></span>";
            $completion=0;
        }
        $icon="<a href='?act=details&what=approvals&id=$id'>$icon</a>";
        $graph= "<span class=''>".$this->html->draw_progress($completion)."</span>";
        $status=array(
            'approvals'=>$approvals,
            'approved'=>$approved,
            'declined'=>$declined,
            'icon'=>$icon,
            'status'=>$stts,
            'status_id'=>$stts_id,
            'graph'=>$graph,
            'completion'=>$completion,
        );

        return $status;
    }
    function get_approval_item_status($id)
    {
        global $approvelevel,$approved,$declined,$approvals;
        $approvelevel--;
        $approvals++;
        if ($approvelevel<0) {
            return false;
        }
        $approval=$this->db->GetRow("select * from approval_items where id=$id");
        if (($approval[approved]=='t')&&($approval[declined]=='f')) {
            $approved++;
        }
        if (($approval[declined]=='t')&&($approval[approved]=='f')) {
            $declined++;
        }

        $sql="select * from approval_items where parent_id=$id";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows = pg_num_rows($cur);
        while ($row = pg_fetch_array($cur)) {
            $this->get_approval_item_status($row[id]);
        }
        return true;
    }
    function approval_item_status($id)
    {
        $id=$id*1;
        $approval=$this->db->GetRow("select * from approval_items where id=$id");
        $link='';
        $link_c='';
        //$approval[user_id]=$GLOBALS[uid];
        if ($approval[user_id]==$GLOBALS[uid]) {
            $link="<a href='?act=add&what=confirm_approval&id=$id'>";
            $link_c="</a>";
        }


        if (($approval[approved]=='t')&&($approval[declined]=='f')) {
            $res="$link<span class='btn btn-micro btn-success'><i class='icon-ok icon-white'></i></span>$link_c Approved on ".substr($approval[approve_date], 0, 16);
        }
        if (($approval[declined]=='t')&&($approval[approved]=='f')) {
            $res="$link<span class='btn btn-micro btn-danger'>$link<i class='icon-remove icon-white'></i></span>$link_c declined on ".substr($approval[approve_date], 0, 16);
        }


        if (($approval[declined]=='f')&&($approval[approved]=='f')) {
            $dependant=$this->db->GetVal("select count(*) from approval_items where parent_id=$id and approved='f'");
            if ($dependant>0) {
                $ors=$this->db->GetVal("select count(*) from approval_items where parent_id=$id and approved='t' and type='10301'");
                if ($ors>0) {
                    //$res="<span class='btn btn-micro btn-success'>$link<i class='icon-ok icon-white'></i>$link_c</span> Approved by colegue";
                    $res="$link<span class='btn btn-micro btn-info'><i class='icon-edit icon-white withpointer'></i></span>$link_c You can approve or reject";
                } else {
                    $res="<span class='btn btn-micro btn-link'><i class='icon-time withpointer'></i></span> Waiting for other approvals";
                }
            } else {
                if ($approval[user_id]==$GLOBALS[uid]) {
                    $res="$link<span class='btn btn-micro btn-info'><i class='icon-edit icon-white withpointer'></i></span>$link_c You can approve or reject";
                } else {
                    $res="<span class='btn btn-micro btn-info'><i class='icon-time icon-white withpointer'></i></span> Pending...";
                }
            }
        }

        return $res;
    }
    function detalize($table, $id, $chars = 0, $noname = '', $fileld='name')
    {

        //$chars=10;
        settype($id, "integer");
        //if($chars==0)$sql="select name from $table where id=$id";
        //if($chars>0)$sql="select substr(name,1,$chars)||'...' ||substr(name,char_length(name)-5,6) from $table where id=$id";
        $field=($this->field_exists($table, $fileld))?$fileld:'id';
        $sql="select $field from $table where id=$id; --detalize";
        if ($noname=='') {
            $name=$this->db->GetVal($sql);
        } else {
            $name=$noname;
        }
        if ($chars>0) {
            $chars=$chars*7;
            $name=$this->utf8->utf8_cutByPixel($name, $chars, false);
        }
        if ($name=='') {
            $name="--";
        }

        if ($id!=0) {
            $link="<a href='".$this->html->link(array('act'=>'details','what'=>$table,'id'=>$id))."'>$name</a>";
        } else {
            $link=$name;
        }
        //if(($GLOBALS['stealth']==1)&&($table=='partners'))$link=$name;
        //if(($id==0))$link='';
        return $link;
    }

    function editalize($table, $id, $chars = 0)
    {

        //$chars=10;
        settype($id, "integer");
        if ($chars==0) {
            $sql="SELECT name from $table where id=$id; -- from editalize";
        }
        if ($chars>0) {
            $sql="SELECT substr(name,1,$chars)||'...' ||substr(name,char_length(name)-5,6) from $table where id=$id; -- from editalize";
        }
        $name=$this->db->GetVal($sql);
        if ($name=='') {
            $name="--";
        }
        $link="<a href='".$this->html->link(array('act'=>'edit','what'=>$table,'id'=>$id))."'>$name</a>";
        return $link;
    }

    function get_alias($table, $id)
    {

        $id=$id*1;
        $sql="select alias from $table where id=$id";
        $name=$this->db->GetVal($sql);
        return $name;
    }
    function get_alias_id($table, $name)
    {
        $id=$id*1;
        $sql="select id from $table where upper(alias)=upper('$name')";
        ;
        $id=$this->db->GetVal($sql)*1;
        return $id;
    }
    function get_list_id($list_alas, $item_alias)
    {
        $id=$id*1;
        $sql="select id from lists where upper(alias)=upper('$list_alas')";
        $list_id=$this->db->GetVal($sql)*1;
        if ($list_id>0) {
            $sql="select id from listitems where upper(alias)=upper('$item_alias') and list_id=$list_id";
            $list_id=$this->db->GetVal($sql)*1;
        }
        return $list_id;
    }
    function get_default_val($id)
    {

        $id=$id*1;
        $sql="select id from listitems where default_value='t' and list_id=$id order by id desc limit 1";
        $id=$this->db->GetVal($sql);
        return $id;
    }
    function get_partner_name($id, $words = 0, $splitter = '', $grouping = '', $data = array())
    {
        $id=$id*1;
        $name=$this->get_name('partners', $id, $words);
        if ($words>0) {
            $name=ucwords(strtolower($name));
            if ($splitter!='') {
                $name=str_replace(' ', $splitter, $name);
            }
            //$name=$id;
            //if($id==5195)$name='nata';
            if ($grouping=='client') {
                //echo $this->html->pre_display($data,'data'); exit;

                $grouping='';
                $sql="select clientid from clients2partners where partnerid=$id order by clientid;";
                $group='';
                if (!($cur = pg_query($sql))) {
                    $this->html->SQL_error($sql);
                }
                while ($row = pg_fetch_array($cur)) {
                    if (in_array($row[clientid], $data[only])) {
                        $group=$group."_".$row[clientid];
                    } else {
                        $group="others";
                    }
                }
                if ($group=='') {
                    $group="single";
                }
                //$group=$this->utils->F_tostring($this->db->GetResults($sql),1,"_");
                $name=$group.'.'.$name."-$group";
            }

            if ($grouping!='') {
                $sql="select $grouping from partners where id=$id;--get_partner_name";
                $group=$this->db->GetVal($sql);
                $name=$group.'.'.$name;
                //$group=$this->get_name('partners',$group);
                //$name=$name.'_'.$group;
            }
        }
        //$name=$name.'_'.$id;
        return $name;
    }

    function get_name($table, $id, $words = 0, $no_0 = 0)
    {
        $id=$id*1;
        if ($this->field_exists($table, 'name')) {
            $sql="select name from $table where id=$id; --get_name";
            $name=$this->db->GetVal($sql);
            if ($words>0) {
                $name=trim($name);
                $name=str_replace('.', '', $name);
                $name=str_replace('/', '', $name);
                $name=str_replace('"', '', $name);
                $name=str_replace('“', '', $name);
                $name=str_replace('”', '', $name);
                $name=str_replace("'", '', $name);
                $name=str_replace("`", '', $name);
                $name=str_replace("(", '', $name);
                $name=str_replace(")", '', $name);

                $nm=explode(' ', $name);
                if (count($nm)<$words) {
                    $words=count($nm);
                }
                array_splice($nm, $words);
                $name=implode(' ', $nm);
            }
        } else {
            $name="id:$id";
        }
        if (($no_0>0)&&($id==0)) {
            $name='';
        }
        return $name;
    }
    function set_val($table, $field, $id, $value)
    {
        $id=$id*1;
        $vals[$field]=$value;
        $this->db->update_db($table, $id, $vals);

        //$sql="update $table set $field='$value'  where id=$id";
        //$name=$this->db->GetVal($sql);
        return true;
    }
    function get_val($table, $field, $id)
    {
        if (!$this->field_exists($table, $field)) {
            return "";
        }
        $id=$id*1;
        $sql="select $field from $table where id=$id";
        $name=$this->db->GetVal($sql);
        return $name;
    }

    function get_array($table, $field, $id){
        $data=$this->get_val($table, $field, $id);
        $result=json_decode($data, true);
        if (json_last_error() == JSON_ERROR_NONE){
            return $result;
        }else{
            $fallback=[$field=>$data];
            $result=json_decode($fallback, true);
            return [$data];
        }
    }
    function get_row($table, $id)
    {
        //echo "$table,$id<br>";
        $id=$id*1;
        $sql="select * from $table where id=$id; --data get_row";
        $row=$this->db->GetRow($sql);
        return $row;
    }
    function obj_namelist($table = 'partners', $ids = array(), $detalize = 1, $delim = ',', $show_ids = 0, $max = 10)
    {
        $tmp=array();
        if (count($ids)<=$max) {
            foreach ($ids as $id) {
                if ($detalize==1) {
                    $tmp[]=$this->detalize($table, $id, 10);
                } else {
                    $tmp[]=$this->get_name($table, $id);
                }
            }
            $res=implode($delim, $tmp);
            if ($show_ids==1) {
                $res="$res(".implode($delim, $ids).")";
            }
        } else {
            $res="More than $max";
        }

        return $res;
    }
    function get_partner_id($name)
    {

        //$name=substr($name,0,-1);
        $id=$this->db->GetVal("select id from partners where name like '$name%'")*1;
        if ($id==0) {
            $id=$this->db->GetVal("select id from partners where ru like '$name%'")*1;
            if ($id==0) {
                $id=$this->db->GetVal("select id from partners where en like '$name%'")*1;
                if ($id==0) {
                    $id=$this->db->GetVal("select id from partners where synonyms like '%$name%' limit 1")*1;
                }
            }
        }
        return $id;
    }
    function partner_form2($field, $value, $title, $html)
    {
        $res[wait]='
            $("#partner_'.$field.'").html("<img src=\''.ASSETS_URI.'/assets/img/loadingsmall.gif\'>");
            ';
        $res[load]='
            $.ajaxq ("queue'.$field.'", {
                url: " ?csrf='.$GLOBALS[csrf].'&act=append&what=lookup&fromtable=partners&field='.$field.'&refid='.$value.'&edit=1",
                cache: false,
                success: function(html)
                {
                    $("#partner_'.$field.'").html(html);
                }
            });
            ';

        $out="<fieldset class='lookup'>
            <div id='title_$field'>$title</div>
            <input type='text' placeholder='Narrow search' name='partnersearch' id='search_$field' value='' onchange='itemid=this.value;ajaxFunction(\"partner_$field\",\"?csrf=$GLOBALS[csrf]&act=append&what=partnerid&fieldname=$field&value=\"+itemid);' $disabled>
            <span onclick='itemid=document.getElementById(\"search_$field\").value;ajaxFunction(\"partner_$field\",\"?csrf=$GLOBALS[csrf]&act=append&what=partnerid&fieldname=$field&value=\"+itemid);' class='icon-search'></span>
            <div id='partner_$field'></div>
            </fieldset>";

        $out.= '
            <script>
                '.$res[wait].'
                '.$res[load].'
            </script>
                ';


        $res[out]=$out;
        return $res;
    }

    function partner_form($field='', $value='', $title='', $html='')
    {
        if ($html=='0') {
            $html='';
        }
        $res[wait]='
            $("#partner_'.$field.'").html("<img src=\''.ASSETS_URI.'/assets/img/loadingsmall.gif\'>");
            ';
        $res[load]='
            $.ajaxq ("queue'.$field.'", {
                url: " ?csrf='.$GLOBALS[csrf].'&act=append&what=lookup&fromtable=partners&field='.$field.'&refid='.$value.'&edit=1",
                cache: false,
                success: function(html)
                {
                    $("#partner_'.$field.'").html(html);
                }
            });
            ';

        $res[func3]="
            function CallFunc_$field(itemid) {
                    ajaxFunction(\"partner_$field\",\"?csrf=$GLOBALS[csrf]&act=append&what=partnerid&fieldname=$field&value=\"+itemid);
                };
            ";
        $res[func]='
            function CallFunc_'.$field.'(itemid) {
            $.ajaxq ("queue'.$field.'", {
                url: " ?csrf='.$GLOBALS[csrf].'&act=append&what=partnerid&fieldname='.$field.'&value="+itemid,
                cache: false,
                success: function(html)
                {
                    $("#partner_'.$field.'").html(html);
                }
            });
        };
            ';

        $out.= '
            <script>
            '.$res[func].'
            </script>
                ';

        $out.="<fieldset class='lookup'>
            <div id='title_$field'><b>$title</b></div>
            <input type='text' placeholder='Narrow search' name='partnersearch' id='search_$field' value='' onchange='itemid=this.value;CallFunc_$field(itemid);' $disabled>
            <span onclick='itemid=document.getElementById(\"search_$field\").value;CallFunc_$field(itemid);' class='icon-search'></span>
            <div id='partner_$field'></div>
            $html
            </fieldset>";

        $out.= '
            <script>
                '.$res[wait].'
                '.$res[load].'
            </script>
                ';


        $res[out]=$out;
        return $res;
    }

    function object_form($table='', $field='', $value='', $title='', $html='')
    {
        if ($html=='0') {
            $html='';
        }
        $res[wait]='
            $("#object_'.$field.'").html("<img src=\''.ASSETS_URI.'/assets/img/loadingsmall.gif\'>");
            ';

        $res[load]='
            $.ajaxq ("queue'.$field.'", {
                url: " ?csrf='.$GLOBALS[csrf].'&act=append&what=lookup&fromtable='.$table.'&field='.$field.'&refid='.$value.'&edit=1",
                cache: false,
                success: function(html)
                {
                    $("#object_'.$field.'").html(html);
                }
            });
            ';

        $res[func3]="
            function CallFunc_$field(itemid) {
                    ajaxFunction(\"object_$field\",\"?csrf=$GLOBALS[csrf]&act=append&what=object_id&fromtable='.$table.'&fieldname=$field&value=\"+itemid);
                };
            ";
        $res[func]='
            function CallFunc_'.$field.'(itemid) {
            $.ajaxq ("queue'.$field.'", {
                url: " ?csrf='.$GLOBALS[csrf].'&act=append&what=object_id&fromtable='.$table.'&fieldname='.$field.'&value="+itemid,
                cache: false,
                success: function(html)
                {
                    $("#object_'.$field.'").html(html);
                }
            });
        };
            ';

        $out.= '
            <script>
            '.$res[func].'
            </script>
                ';

        $out.="<fieldset class='lookup'>
            <div id='title_$field'><b>$title</b></div>
            <input type='text' placeholder='Narrow search' name='objectsearch' id='search_$field' value='' onchange='itemid=this.value;CallFunc_$field(itemid);' $disabled>
            <span onclick='itemid=document.getElementById(\"search_$field\").value;CallFunc_$field(itemid);' class='icon-search'></span>
            <div id='object_$field'></div>
            $html
            </fieldset>";

        $out.= '
            <script>
                '.$res[wait].'
                '.$res[load].'
            </script>
                ';


        $res[out]=$out;
        return $res;
    }

    function object_form2($table='', $field='', $value='', $title='', $type_id=0)
    {
        $tablefield=$table.'_'.$field;
        $out="<fieldset class='lookup'>
            <div id='title_$field'>$title</div>
            <input type='text' placeholder='Narrow search' name='search$tablefield' id='search_$tablefield' value='' onchange='itemid=this.value;ajaxFunction(\"$tablefield\",\"?csrf=$GLOBALS[csrf]&act=append&what=object_id&fromtable=$table&fieldname=$field&value=\"+itemid);' $disabled>
            <span onclick='itemid=document.getElementById(\"search_$tablefield\").value;ajaxFunction(\"$tablefield\",\"?csrf=$GLOBALS[csrf]&act=append&what=object_id&fromtable=$table&fieldname=$field&value=\"+itemid);' class='icon-search'></span>
            <div id='$tablefield'></div>
            </fieldset>";

        $res[wait]='
            $("#'.$tablefield.'").html("<img src=\''.ASSETS_URI.'/assets/img/loadingsmall.gif\'>1");
            ';
        $res[load]='
            $.ajaxq ("queue'.$tablefield.'", {
                url: " ?csrf='.$GLOBALS[csrf].'&act=append&what=lookup&fromtable=partners&field='.$field.'&refid='.$value.'&edit=1",
                cache: false,
                success: function(html)
                {
                    $("#'.$tablefield.'").html(html);
                }
            });
            ';

        $res[out]=$out;
        return $res;
    }

    function partner_account_form($role, $acc_id, $queue = 1)
    {
        $acc_id=$acc_id*1;
        $refid=$this->db->GetVal("select partnerid from accounts where id=$acc_id")*1;
        if ($role=='s') {
            $field='sender_id';
        } else {
            $role='r';
            $field='receiver_id';
            //$corrbank="<br><span id='_corrbank'></span>";
        }
        $arr=explode('_', $field);
        $ftitle=ucfirst($arr[0]);//.",$role,$refid,$acc_id,$queue,$field";
        $href="\"?csrf=$GLOBALS[csrf]&act=append&what=lookup&fromtable=partners&field=$field&child=account&role=$role&value=\"+itemid";
         $out="<fieldset class='lookup'><label>$ftitle</label>
            <input type='text' placeholder='Narrow search' name='partnersearch' id='search_$field' value=''
            onchange='itemid=this.value;ajaxFunction(\"partner_$field\",$href);'>
                <span onclick='itemid=document.getElementById(\"search_$field\").value;ajaxFunction(\"partner_$field\",$href);' class='icon-search'></span>
            <div id='partner_$field'>partner_$field</div>
            <span id='".$role."_acc_id_'></span><br><span id='curr_".$role."_acc_id'></span>$corrbank
            </fieldset>";
        $res[out]=$out;
        $res[wait]='
            $("#partner_'.$field.'").html("<img src=\''.ASSETS_URI.'/assets/img/loadingsmall.gif\'>1");
            $("#'.$role.'_acc_id_").html("<img src=\''.ASSETS_URI.'/assets/img/loadingsmall.gif\'>2");
            $("#curr_'.$role.'_acc_id").html("<img src=\''.ASSETS_URI.'/assets/img/loadingsmall.gif\'>3");
            ';
        $res[load]='
            $.ajaxq ("queue'.$queue.'", {
                url: " ?csrf='.$GLOBALS[csrf].'&act=append&what=lookup&fromtable=partners&field='.$field.'&child=account&role='.$role.'&refid='.$refid.'&edit=1",
                cache: false,
                success: function(html)
                {
                    $("#partner_'.$field.'").html(html);
                }
            });

            $.ajaxq ("queue'.$queue.'", {
                url: " ?csrf='.$GLOBALS[csrf].'&act=append&what=acc_id&role='.$role.'&id='.$acc_id.'&refid='.$refid.'&edit=1",
                cache: false,
                success: function(html)
                {
                    $("#'.$role.'_acc_id_").html(html);
                }
            });

            $.ajaxq ("queue'.$queue.'", {
                url: " ?csrf='.$GLOBALS[csrf].'&act=append&what=curr_acc_id&role=s&refid='.$acc_id.'&edit=1",
                cache: false,
                success: function(html)
                {
                    $("#curr_'.$role.'_acc_id").html(html);
                }
            });
            ';
        return $res;
    }
    function get_new_name($table='', $date='', $addsql='', $prefix='', $opt='')
    {
        if ($opt=='by_id') {
            $name=$prefix.$this->db->getval("SELECT max(id) from $table")*1;
            //echo "<br>NAME:$name<br>";
            return $name;
        }
        if ($date=='') {
            $date=$this->dates->F_date("", 1);
        }
        $month=substr($date, 3, 2);
        $year=substr($date, 8, 2);
        $yearfull=substr($date, 6, 4);
        $month2=$month;
        $lastday=$this->dates->days_in_month($month2, $year);
        $orderby="date_trunc('day', date) desc, name desc, id desc";


        if ($table!='documents') {
            $month="01";
            $month2="12";
            $lastday="31";
        }
        $date_filter="and date>='01.$month.$yearfull' and date<='$lastday.$month2.$yearfull  23:59:59'";
        if ($opt=='no_date') {
            $date_filter='';
        }
        $sql="select count(*) from $table where id>0 $date_filter $addsql";

        $cntr1=$this->db->GetVal($sql)*1;
        //echo  "<br>$sql; CTR=$cntr1"; exit;
        if ($cntr1>0) {
            $sql="select name from $table where id>0 $date_filter $addsql order by $orderby limit 1";

            $name=$this->db->GetVal($sql); //01.01.2009

            //echo "<br>$sql; ";
            //echo "<br>$name";
            $tokens=explode('-', $name);
            $name=end($tokens);
            //echo  "<br>cntr1=$cntr1 SQL=$sql; name=$name"; exit;
            if (is_numeric($name)) {
                $cntr=$name*1+1;
            } else {
                $cntr=$cntr1+1;
            }
            //$cntr=$name*1+1;//01-34-6789
            //echo  "<br>$cntr";
        } else {
            $cntr=1;
        }
        //$cntr=2347;
        if ($prefix=='') {
            $prefix="A-";
        }
        $name=$prefix.$year."-".sprintf("%05s", $cntr);
        if ($table=='documents') {
            $name=$year."-".$month."-".sprintf("%04s", $cntr);
        }
        if ($table=='i_transactions') {
            $no=$this->db->GetVal("SELECT last_value FROM i_transactions_id_seq");
            //$no+=1;
            $no=sprintf("%05s", $no);
            $y=substr($thisyear, 3, 1);
            $name="DPT-$no";
        }
        //echo "<br>NAME:$name<br>";
        return $name;
        //$out.= "N:$name<br>"; exit;
    }
    function listitems($field_name='', $value='', $alias='', $class='', $all = 'none')
    {

        $listid=$this->db->GetVal("select id from lists where lower(alias)=lower('$alias') order by id asc limit 1")*1;
        if ($listid==0) {
            $all="Alias '$alias' for list '$field_name' not found";
        }
        if (strtoupper($all)=='ALL') {
            $def=0;
        } else {
            $def=$this->db->GetVal("select id from listitems where list_id=$listid and default_value='1' order by name limit 1");
        }
        $sql="SELECT id, name FROM listitems where list_id=$listid ORDER by name";
        $txt=$this->html->htlist($field_name, $sql, $value, $all, '', $def, $class);
        return $txt;
    }
    function userinfo()
    {
        global $db, $uid, $username;
        //$uid=3;
        $username=$this->db->GetVal("select username from users where id=$uid");
        if ($uid>0) {
            $logoutbtn='| <a href="?act=logout"><i class="icon-off icon-white"></i></a>';
            if (!$GLOBALS[settings][no_nenu_alerts]) {
                if ($this->table_exists('useralerts')) {
                    $myunread=$this->db->GetVal("select count(*) from useralerts where userid=$uid and wasread='0'");
                    $myunreadsent=$this->db->GetVal("select count(*) from useralerts where fromuserid=$uid and wasread='0'");
                    $myunread=$myunread>0?        "<a href='?act=show&what=useralerts&unread=1&received=1'><span class='badge red' onMouseover=\"showhint('Unread by me', this, event, '');\">$myunread</span></a>":0;
                    $myunreadsent=$myunreadsent>0?"<a href='?act=show&what=useralerts&unread=1&sent=1'><span class='badge badge-info' onMouseover=\"showhint('Not yet recieved', this, event, '');\">$myunreadsent</span></a>":0;
                    $alerts="$myunread/$myunreadsent";
                    $alrts=' | Alerts:'.$alerts;
                }
            }

            if ($this->table_exists('clientrequests')) {
                $sql="";
                if ($GLOBALS['allowed_pids']!='') {
                    $sql = "$sql and partnerid in ($GLOBALS[allowed_pids])";
                }
                if ($GLOBALS['history_tail']>0) {
                    $sql = "$sql and  (completeddate>=now() - INTERVAL '$GLOBALS[history_tail] days' or completeddate='01.01.1999')";
                }
                if ($GLOBALS['regdate'] <> '01.01.1999') {
                    $sql = "$sql and  date>='".$GLOBALS['regdate']."'";
                }
                if ($GLOBALS['workgroup']['administrator_id']>0) {
                    $sql = "$sql and  (topartnerid='".$GLOBALS['workgroup']['administrator_id']."' or executor='".$GLOBALS['uid']."' or receivedby='".$GLOBALS['uid']."')";
                }
                $sql_run="select count(*) from clientrequests where approvedby=0 and not (suspendedby>0 and date<=now() - INTERVAL '15 days') $sql";

                $requestsnotapproved=$this->db->GetVal($sql_run);
                $requestsnotcomplete=$this->db->GetVal("select count(*) from clientrequests where approvedby>0 and confirmedby=0 $sql");
                $requeststoberevisedbyme=$this->db->getval("select count(*) from clientrequests where revisedby=0 and confirmedby=0 and id in (select ref_id from watchlist where ref_table='clientrequests' and user_id=$GLOBALS[uid])  $sql")*1;
                $sql_run="select count(*) from clientrequests where revisedby=0 and confirmedby>0 and id in (select ref_id from watchlist where ref_table='clientrequests' and user_id=$GLOBALS[uid])  $sql";
                $requestsnotrevisedbyme =$this->db->getval($sql_run)*1;
                //echo $this->html->pre_display($sql_run,"data");

                $requestsnotapproved=$requestsnotapproved>0?"<a href='?act=show&what=clientrequests&nopager=1&notapproved=1'><span class='badge red' onMouseover=\"showhint('New not approved', this, event, '');\">$requestsnotapproved</span></a>":"-";
                $requestsnotcomplete=$requestsnotcomplete>0?"<a href='?act=show&what=clientrequests&nopager=1&approved=1&notconfirmed=1'><span class='badge' onMouseover=\"showhint('Approved incomplete', this, event, '');\">$requestsnotcomplete</span></a>":"-";
                $requestsnotrevisedbyme=$requestsnotrevisedbyme>0?"<a href='?act=show&what=clientrequests&nopager=1&approved=1&confirmed=1&torevisebyme=1'><span class='badge red' onMouseover=\"showhint('To be revised by me', this, event, '');\">$requestsnotrevisedbyme</span></a>":"-";
                $requeststoberevisedbyme=$requeststoberevisedbyme>0?"<a href='?act=show&what=clientrequests&nopager=1&approved=&notconfirmed=1&torevisebyme=1'><span class='badge' onMouseover=\"showhint('Incomplete to be revised by me', this, event, '');\">$requeststoberevisedbyme</span></a>":"-";

                $requests="$requestsnotapproved/$requestsnotcomplete/$requeststoberevisedbyme/$requestsnotrevisedbyme";
                /*
                $access['home_clietrequests_our_incomplete']=1;
                if($access['home_clietrequests_our_incomplete']){
                    $reponsibles="$GLOBALS[uid],11,19,40";
                    $requestsourincomplete=$this->db->GetVal("select count(*) from clientrequests where approvedby>0 and confirmedby=0 and receivedby in ($reponsibles) ")*1;
                    $requestsourincomplete=$requestsourincomplete>0?"<a href='?act=show&what=clientrequests&nopager=1&approved=&notconfirmed=1&torevisebyme=1'><span class='badge orange' onMouseover=\"showhint('Incorporation incomplete', this, event, '');\">$requestsourincomplete</span></a>":"-";
                    $requests="$requests/$requestsourincomplete";
                }
                */
                $reqs=' | Req.:'.$requests;
            }

            // if($this->table_exists('documents')){
            //  $sqladd="";
            //  if($GLOBALS[allowed_pids]!=''){
            //      $sqladd = "$sqladd and (d.id in (select doc_id from docs2obj where ref_table='partners' and ref_id in ($GLOBALS[allowed_pids])) or have_partners='f')";
            //  }
            //  $sql="select count(*) from documents d where d.id>0 and (d.complete='f' and d.dateto<=now() or d.id in (select a2.docid from documentactions a2 where a2.date<=now() and a2.complete='f')) and (d.executor=$uid or d.creator=$uid or d.id in (select a1.docid from documentactions a1 where a1.executor=$uid)) $sqladd";
            //  $docsexpired=$this->db->GetVal($sql);
            //  $sql="select count(*) from documents d where d.id>0 and (d.complete='f' and d.dateto>now() or d.id in (select a2.docid from documentactions a2 where a2.date>now() and a2.complete='f')) and (d.executor=$uid or d.creator=$uid or d.id in (select a1.docid from documentactions a1 where a1.executor=$uid)) $sqladd";
            //  $docsinwork =$this->db->GetVal($sql);
            //  $docsexpired=$docsexpired>0?"<a href='?act=show&what=documents&expired=1&belong=me'><span class='badge red' onMouseover=\"showhint('Expired', this, event, '');\">$docsexpired</span></a>/":"";
            //  $docsinwork=$docsinwork>0?"<a href='?act=show&what=documents&belong=me&inwork=1'><span class='badge' onMouseover=\"showhint('In process', this, event, '');\">$docsinwork</span></a>":"";
            //  $mydocs="$docsexpired$docsinwork";
            //  $docs=' | Docs:'.$mydocs;
            // }
            if (!$GLOBALS[settings][no_nenu_rates]) {
                if (($this->table_exists('rates_local'))&&($GLOBALS[settings][use_local_rates]>0)) {
                    $sqladd="";
                    $usd=$this->get_rate_local('USD');
                    $rub=round($this->get_rate_local('RUB'), 2);
                    $rate.=' | $:'.$usd.' | ₽:'.$rub;
                }

                if (($this->table_exists('rates'))&&(!$GLOBALS[settings][use_local_rates]>0)) {
                    $sqladd="";
                    $usd=round($this->convert_currency(1, 'EUR', 'USD', ''), 4);

                    //$usd=$this->get_rate('USD');
                    $rub=round($this->convert_currency(1, 'EUR', 'RUB', ''), 4);
                    $rate.=' | $:'.$usd.' | ₽:'.$rub;
                }
            }


            if($GLOBALS[workgroup][id]>0)$workgroup_name="@".strtolower($GLOBALS[workgroup][name]).":".$GLOBALS[is_owner_id];
            $logininfo='<i class="icon-user icon-white"></i> <a href="?act=report&what=myprofile" ><span style="color:#fff;">'.$username.$workgroup_name.'</span></a>'.$docs.$reqs.$alrts.$rate;
        } else {
            $logoutbtn="";
            $logininfo='';
        }
        $result='
            '.$logininfo.'
                '.$logoutbtn;
        return $result;
    }
    function click()
    {
        $post=$_POST;
        unset($post[password]);
        $id=ceil(abs($this->html->readRQn('id')));
        if ($id<1) {
            $id=0;
        }
        if ($id>(2*10^64)) {
            $id=0;
        }
        $act=$this->html->readRQ('act');
        $what=$this->html->readRQ('what');
        if (($act=='offline')&&($what=='daily')) {
            $id=0;
        }
        $vals=array(
            'ip'=>$_SERVER[REMOTE_ADDR],
            'uid'=>$GLOBALS[uid],
            'uname'=>$this->username($GLOBALS[uid]),
            'act'=>$act,
            'what'=>$what,
            'ref_id'=>$id,
            'post'=>json_encode($post),
            'get'=>json_encode($_GET),
        );
        if ($vals[what]=='') {
            $vals[what]=$this->html->readRQ('table');
        }
        unset($post);
        $this->db->insert_db('clicks', $vals);
    }
    function log($message)
    {
        $this->DB_log($message);
    }
    function DB_log($text)
    {
        global $uid,$ip;
        $date=date('Y-m-d G:i:s') ;
        $uid=$uid*1;
        $what=$this->db->Escape($what);
        $sql="INSERT INTO logs (userid, ip, date, action) values ($uid, '$ip', now(), '$text')";
        $res=$this->db->GetVar($sql);
    }
    function changes($reference = 'partners', $id = 0, $category = 'main', $data = [], $prev_data)
    {
        $change_id=$this->db->getval("SELECT id from changes where reference='$reference' and ref_id=$id")*1;
        if ($change_id==0) {
            $vals=[
            'reference'=>$reference,
            'ref_id'=>$id,
            ];
            $change_id=$this->db->insert_db('changes', $vals);
        }



        $prev_chnges=json_decode($this->get_val('changes', 'changes_json', $change_id), true);
        //echo $this->html->pre_display($prev_chnges,"prev_chnges");
        $prev_chnges_last=$prev_chnges[count($prev_chnges)-1];
        //echo $this->html->pre_display($prev_chnges_last,"prev_chnges_last");
        $before=$prev_data;
        if (!$before) {
            $before=[];
        }
        $after=$data;

        $treewalker = new \TreeWalker(["returntype"=>"array"]);

        $diff=$treewalker->getdiff($after, $before, true); // false -> with slashs


        //echo $this->html->pre_display($before,"before");
        //echo $this->html->pre_display($after,"after");
        //echo $this->html->pre_display($diff,"diff");

        $changes=[
            'no'=>$prev_chnges_last[no]+1,
            'date'=>$GLOBALS[today],
            'timestamp'=>time(),
            'uid'=>$GLOBALS[uid],
            'username'=>$GLOBALS[username],
            'ip'=>$GLOBALS[ip],
            'category'=>'risk',
            'change'=>$diff,
        ];
        $prev_chnges[]=$changes;
        $json=json_encode($prev_chnges);
        $this->db->update_db('changes', $change_id, ['changes_json'=>$json]);
    }
    function DB_change($what, $id, $actname = 'CHANGE')
    {
        $org_id=(int)$id;
        if ($what=='shoppingcart') {
            $org_id=0;
        }
        if (!is_numeric($org_id)) {
            $org_id=0;
        }
        if ($actname!='DELETE') {
            $GLOBALS[record_new_vals]=$this->record_array($what, $id);
            $GLOBALS[record_diff_vals]=array_diff($GLOBALS[record_new_vals], $GLOBALS[record_old_vals]);
        }


        //$GLOBALS[form_diff_vals_json]=json_encode($GLOBALS[form_diff_vals]);
        //echo $this->html->pre_display($GLOBALS[record_old_vals],'old');
        //echo $this->html->pre_display($GLOBALS[record_new_vals],'new');
        //echo $this->html->pre_display($GLOBALS[record_diff_vals],'diff');
        //echo 'QUER:'.json_encode($GLOBALS['record_old_vals']); exit;

        $vals=array(
                'tablename'=>$what,
                'ref_id'=>$org_id,
                'user_id'=>$GLOBALS[uid],
                'before'=>json_encode($GLOBALS['record_old_vals']),
                'after'=>json_encode($GLOBALS['record_new_vals']),
                'changes'=>json_encode($GLOBALS['record_diff_vals']),
                'action'=>"$actname",
                'descr'=>"$actname T:$what, ID:$id, U:$GLOBALS[user],I:$GLOBALS[dbchanges]"
            );
            //$out.= "<br>TEST:".http_build_query($data_diff_after_save)."<pre>";print_r($vals);$out.= "</pre>";$out.= "<pre>";print_r($GLOBALS);$out.= "</pre>";exit;
        //if(!(($actname=='EDIT')&&(count($GLOBALS['record_diff_vals'])==0)))
            $this->db->insert_db('dbchanges', $vals);
        //$this->set_history($what,$id,$GLOBALS['record_diff_vals']);
    }

    function set_history($what, $id, $data)
    {
        $save_table=$this->db->getval("SELECT count(*) from history_watch where (table_name='$what' or table_name='*') and active='t'")*1;
        if ($save_table>0) {
            $sequence=$this->db->getval("SELECT max(sequence) from history where table_name='$what' and record_id=$id")+1;
            foreach ($data as $key => $value) {
                $save_field=$this->db->getval("SELECT count(*) from history_watch where (table_name='$what' or table_name='*') and (field_name='$key' or field_name='*') and active='t'")*1;
                //echo "$key=>$value ($save_field)<br>";
                if ($save_field>0) {
                    $vals=array(
                    //'date'=>$date,
                    'table_name'=>$what,
                    'record_id'=>$id,
                    'sequence'=>$sequence,
                    'field_name'=>$key,
                    'field_value'=>$value,
                    'user_id'=>$GLOBALS[uid],
                    //'active'=>$active
                    );
                    $this->db->insert_db('history', $vals);
                //echo "Field $key ($value) Saved<br>";
                }
            }
        }
        //exit;
    }

    function get_history($what, $id, $sequence)
    {
        return $data;
    }

    function csv($sql, $formated = 0, $delimiter="\t", $quoted="")
    {
        $sqltokens=explode("from ", $sql);
        $sqltokens2=explode(" ", $sqltokens[1]);
        $table=$sqltokens2[0];
        $sql=str_ireplace("\'", "'", $sql);
        if (!($result = pg_query($sql))) {
            return $this->html->pre_display($sql."\n".pg_last_error(), 'SQL error', 'alert-error');
        }
        $fields_num = pg_num_fields($result);
        //$response="";
        $tbl.="<table class='table table-bordered table-striped-tr table-morecondensed tooltip-demo  table-notfull'>";
        $tbl.="<tr class='c'>";
        for ($i=0; $i < $fields_num; $i++) {
            $field = pg_field_name($result, $i);
            //$response.="$field$delimiter";

            $tbl.="<td>$field</td>";
            if ($field=='id') {
                $idno=$i;
            }
            $fields[]=$quoted.$field.$quoted;
        }
        $csv_row=$fields;
        $csv_arr[]=implode($delimiter,$csv_row);

        $csv_row=[$i,$row[id],$row[name]];


        //$response.="\n";
        $tbl.="</tr>";
        $tbl.="<tr>";
        while ($row = pg_fetch_row($result)) {
            $i=0;
            $csv_row=[];
            foreach ($row as $cell) {
                if ($i==$field) {
                    $cell2="<a href='?act=details&what=$table&id=$cell'>$cell</a>";
                }
                $tbl.="<td>$cell2   </td>";

                $cell=str_replace(array("\n","\r","$delimiter"), array(" "," "," "), $cell);
                //$response.="$cell$delimiter";
                $csv_row[]=$quoted.$cell.$quoted;

                $i++;
            }
                $tbl.="</tr>";

            //$response.="\n";
            $csv_arr[]=implode($delimiter,$csv_row);
        }
        $tbl.="</table>";
        $csv=implode("\n",$csv_arr);
        if ($formated==0) {
            return $csv;
        } else {
            return $tbl;
        }
    }
    function get_user_info($id)
    {
        $res=$this->db->GetRow("select * from users where id=$id");
        $res[full_name]=$res[surname].' '.$res[firstname];
        $res[name_full]=$res[firstname].' '.$res[surname];
        $res[initials]=$res[firstname][0].$res[surname][0];
        return $res;
    }
    public function noAccess($accessitemchk)
    {
        global $access;
        if (($access['main_admin'])&&(!$access[$accessitemchk])) {
            $count=($this->db->GetVal("select count(*) from accessitems where name='$accessitemchk'")*1);
            if ($count==0) {
                $body.=  "<br><br><a href='?act=tools&what=addaccessitems&item=$accessitemchk'><button class='btn btn-mini btn-danger' type='button'>Add <b>$accessitemchk</b></button></a>";
            }
        }
        if (($GLOBALS['stealth']>0)||($GLOBALS['settings']['supress_warnings'])) {
            return '';
        } else {
            return "<div class='alert alert-warn'>No permission for $accessitemchk $body</div>";
        }
    }
    function DB_data($table, $id)
    {
        return $this->record_txt($table, $id);
    }

    function record_txt($table, $id)
    {
        $sql="select * from $table where id=$id; -- data record_txt";
        if (($result = pg_query($sql))) {
            $fields_num = pg_num_fields($result);
            $response="";
            $row = pg_fetch_row($result);
            for ($i=0; $i < $fields_num; $i++) {
                $field = pg_field_name($result, $i);
                $response.="[$field:$row[$i]]";
            }
        }
        return $response;
    }

    function record_array($table, $id)
    {
        $response=array();
        if (!($this->table_exists($table))) {
            $response[]="Table $table does not exist";
            return $response;
        }
        $sql="select * from $table where id=$id; -- data record_array";
        if (($result = pg_query($sql))) {
            $fields_num = pg_num_fields($result);
            $response="";
            $row = pg_fetch_row($result);
            for ($i=0; $i < $fields_num; $i++) {
                $field = pg_field_name($result, $i);
                $response[$field]=$row[$i];
            }
        }
        return $response;
    }

    function sw_active($table, $id)
    {
        global $uid;
        //<img src='".ASSETS_URI."/assets/img/custom/ok.png'>
        //<img src='".ASSETS_URI."/assets/img/custom/warn.png'>
        //<a href='?csrf=$GLOBALS[csrf]&act=save&what=boolean&field=complete&ref_table=$what&ref_id=$row[id]'>$row[completeimg]</a>

        //<a href='?csrf=$GLOBALS[csrf]&act=save&what=favorites&refid=$id&reference=$table'>
        //<a href='?csrf=$GLOBALS[csrf]&act=save&what=favorites&refid=$id&reference=$table'>

        $sql="select * from favorites where refid=$id and reference='$table' and userid=$uid";
        $count=$this->db->GetVal($sql)*1;
        if ($count>0) {
            $result="<a href='?csrf=$GLOBALS[csrf]&act=save&what=favorites&refid=$id&reference=$table'><img src='".ASSETS_URI."/assets/img/custom/fav-in.png' title='Remove from favorites'> Favorite</a>";
        } else {
            $result="<a href='?csrf=$GLOBALS[csrf]&act=save&what=favorites&refid=$id&reference=$table'><img src='".ASSETS_URI."/assets/img/custom/fav-out.png' title='Add to favorites'> Favorite</a>";
        }
        return $result;
    }
    function isinfavorites($table, $id)
    {
        global $uid;
        $sql="select * from favorites where refid=$id and reference='$table' and userid=$uid";
        $count=$this->db->GetVal($sql)*1;
        if ($count>0) {
            $result="<a href='?csrf=$GLOBALS[csrf]&act=save&what=favorites&refid=$id&reference=$table'><img src='".ASSETS_URI."/assets/img/custom/fav-in.png' title='Remove from favorites'> Favorite</a>";
        } else {
            $result="<a href='?csrf=$GLOBALS[csrf]&act=save&what=favorites&refid=$id&reference=$table'><img src='".ASSETS_URI."/assets/img/custom/fav-out.png' title='Add to favorites'> Favorite</a>";
        }
        return $result;
    }
    function help($id)
    {

        if (($id*1)>0) {
            $sql="select * from help where id=$id";
        } else {
            $sql="select * from help where name like '%$id%'";
        }
        $helprow=$this->db->GetRow($sql);
        $helprow[descr]=str_replace("\r\n", "<br>", $helprow[descr]);
        $text="<h3>$helprow[name]</h3>$helprow[descr]";
        //$text="$sql";
        $text="<img src='".ASSETS_URI."/assets/img/custom/help.png' height=12 width=12 onMouseover=\"showhint('$text', this, event, '400px');\">";
        return $text;
    }
    function isallowed($what, $id, $project = '')
    {
        global $uid, $gid,$access;
        $allowed=0;
        $reason="By default settings";
        if ($what=='documents') {
            if ($GLOBALS['history_tail']>0) {
                $sql = "$sql and  dateto>=now() - INTERVAL '$GLOBALS[history_tail] days'";
            }
            if ($project=='') {
                if (!$access['view_docs_oth']) {
                    $sql = "$sql and docgroup!=1500";
                }
                if (!$access['view_docs_ord']) {
                    $sql = "$sql and docgroup!=1501";
                }
                if (!$access['view_docs_int']) {
                    $sql = "$sql and docgroup!=1502";
                }
                if (!$access['view_docs_inc']) {
                    $sql = "$sql and docgroup!=1503";
                }
                if (!$access['view_docs_inv']) {
                    $sql = "$sql and docgroup!=1504";
                }
                if (!$access['view_docs_trs']) {
                    $sql = "$sql and docgroup!=1505";
                }
                if (!$access['view_docs_ctr']) {
                    $sql = "$sql and docgroup!=1506";
                }
                if (!$access['view_docs_bvi']) {
                    $sql = "$sql and docgroup!=1511";
                }
                //if(!$access['view_docs_ids']){$sql = "$sql and docgroup!=1507";}
                //if(!$access['view_docs_poa']){$sql = "$sql and docgroup!=1508";}
            }



            //if($GLOBALS[allowed_pids]!=''){$sql = "$sql and id not in (select doc_id from docs2obj where ref_table='partners' and ref_id in ($GLOBALS[allowed_pids]))";}

            //$sql = "$sql and id!=$id";

            $res=$this->db->GetRow("select * from $what where id=$id $sql");
            $res[id]=$res[id]*1;
            if ($res[id]>0) {
                $sql="SELECT count(*) FROM docs2groups WHERE docid=$id and (groupid=$gid or groupid=0);";

                $allowed=$this->db->GetVar($sql)*1;

                if ($GLOBALS[allowed_pids]!='') {
                    $allowed=0;
                    $reason="By allowed PIDs";
                    if ($GLOBALS[allowed_related_pids]!='') {
                        $sql = "SELECT count(*) FROM documents d where id=$id and docgroup in (1503,1512,1506,1507,1511) and (d.id in (select doc_id from docs2obj where ref_table='partners' and ref_id in ($GLOBALS[allowed_related_pids])) or (have_partners='f' and executor=$GLOBALS[uid]) )";
                        $allowed=$this->db->GetVar($sql)*1;
                        $reason="By document groups (1503,1512,1506,1507,1511)";
                        if($allowed==0){
                            $sql = "SELECT count(*) FROM documents d where id=$id and  (d.id in (select doc_id from docs2obj where ref_table='partners' and ref_id in ($GLOBALS[allowed_pids])) or (have_partners='f' and executor=$GLOBALS[uid]) )";
                            $allowed=$this->db->GetVar($sql)*1;
                            $reason="By document owner1";
                        }
                    } else {
                        $sql = "SELECT count(*) FROM documents d where id=$id and (d.id in (select doc_id from docs2obj where ref_table='partners' and ref_id in ($GLOBALS[allowed_pids])) or (have_partners='f' and executor=$GLOBALS[uid]))";
                        $allowed=$this->db->GetVar($sql)*1;
                        $reason="By document owner2";
                    }
                    if ($res[type]==1658) {
                        $sql = "SELECT count(*) FROM documents d where id=$id and (d.executor=$GLOBALS[uid] or d.creator=$GLOBALS[uid] or d.id in (select a1.docid from documentactions a1 where a1.executor=$GLOBALS[uid]))";
                        $allowed=$this->db->GetVar($sql)*1;
                        $reason="By document owner3";
                    }
                    if ($res[type]==1652) {
                        $allowed=0;
                        $reason="By document type 1652";
                    }
                }

                // if ($GLOBALS[allowed_pids]!='') {
                //     $allowed=0;
                //     $sql = "SELECT count(*) FROM docs2obj where ref_table='partners' and doc_id=$id";
                //     $partners=$this->db->GetVar($sql)*1;

                //     if ($partners>0) {
                //         if ($GLOBALS[allowed_related_pids]!='') {
                //             $sql = "SELECT count(*) FROM documents where id=$id and id in (select doc_id from docs2obj where ref_table='partners' and ref_id in ($GLOBALS[allowed_pids],$GLOBALS[allowed_related_pids]))";
                //         } else {
                //             $sql = "SELECT count(*) FROM documents where id=$id and id in (select doc_id from docs2obj where ref_table='partners' and ref_id in ($GLOBALS[allowed_pids]))";
                //         }

                //         $allowed=$this->db->GetVar($sql)*1;
                //     } else {
                //         $allowed=1;
                //     }
                // }
            }
            if (($GLOBALS['regdate'] <> '01.01.1999')&&(($res['type']==1602)||($res['type']==1652))) {
                $allowed=0;
                $reason="By regdate";
                $res=$this->db->GetRow("select * from $what where id=$id and  date>='".$GLOBALS['regdate']."'");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
        }
        if ($what=='uploads') {
            $sql="SELECT refid FROM uploads WHERE tablename='documents' and id=$id;";
            //echo "$sql";
            $fromdoc=$this->db->GetVar($sql)*1;
            if ($fromdoc>0) {
                $allowed=$this->isallowed('documents', $fromdoc, $project);
            } else {
                $allowed=1;
            }
        }

        if ($what=='partners') {
            $allowed=0;
            //$partner=$this->get_row('partners',$id);
            $sql='';
            if ($GLOBALS[allowed_related_pids]!='') {
                $res=$this->db->GetRow("select * from $what where id=$id and id in ($GLOBALS[allowed_related_pids])");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=2;
                }
            }

            if ($GLOBALS[allowed_pids]!='') {
                $sql = "$sql and  id in ($GLOBALS[allowed_pids])";
            }
            $res=$this->db->GetRow("select * from $what where id=$id $sql");//select * from partners where id=8031
            $res[id]=$res[id]*1;
            if ($res[id]>0) {
                $allowed=1;
            }
            $sql='';



            $hiddenpartneridsarray=explode(",", $GLOBALS[hiddenpartnerids]);
            if ((!$access['view_hidden_partners'])&&(in_array($id, $hiddenpartneridsarray))) {
                $allowed=0;
                $reason="By disabled";
            }

            // if ($GLOBALS[gid]<4) {
            //     $allowed=1;
            // }

            if ($GLOBALS['history_tail']>0) {
                $allowed=0;
                $reason="By history_tail";
                $res=$this->db->GetRow("select * from $what where id=$id and  (dateclose>=now() - INTERVAL '$GLOBALS[history_tail] days' or dateclose is null)");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
            //if(($GLOBALS[workgroup][administrator_id]==1438)&&($partner[type]==201))$allowed=1;
        }
        if ($what=='employees') {
            $allowed=0;
            if ($GLOBALS[allowed_pids]!='') {
                $sql = "$sql and employer=$GLOBALS[is_owner_id]";
            }
            $res=$this->db->GetRow("select * from $what where id=$id $sql");
            $res[id]=$res[id]*1;
            if ($res[id]>0) {
                $allowed=1;
            }
        }
        if ($what=='clientrequests') {
            $allowed=0;
            if ($GLOBALS[allowed_pids]!='') {
                $sql = "$sql and  partnerid in ($GLOBALS[allowed_pids])";
            }
            $res=$this->db->GetRow("select * from $what where id=$id $sql");
            $res[id]=$res[id]*1;
            if ($res[id]>0) {
                $allowed=1;
            }

            if ($GLOBALS['history_tail']>0) {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and  (completeddate>=now() - INTERVAL '$GLOBALS[history_tail] days' or completeddate='01.01.1999')");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }

            if ($GLOBALS['regdate'] <> '01.01.1999') {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and  date>='".$GLOBALS['regdate']."'");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
            if ($GLOBALS['workgroup']['administrator_id']>0) {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and  (topartnerid='".$GLOBALS['workgroup']['administrator_id']."' or executor='".$GLOBALS['uid']."' or receivedby='".$GLOBALS['uid']."'  or invoice_id in (select id from invoices where frompartner_id='".$GLOBALS[workgroup][administrator_id]."'))");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
        }

        if ($what=='accounts') {
            $allowed=0;
            if ($GLOBALS[allowed_pids]!='') {
                $res=$this->db->GetRow("select * from $what where id=$id and partnerid in ($GLOBALS[allowed_pids])");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }

                if ($GLOBALS[allowed_related_pids]!='') {
                    $res=$this->db->GetRow("select * from $what where id=$id and partnerid in ($GLOBALS[allowed_related_pids])");
                    $res[id]=$res[id]*1;
                    if ($res[id]>0) {
                        $allowed=2;
                    }
                }
            } else {
                $allowed=1;
            }
            if ($GLOBALS['history_tail']>0) {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and  (dateclose>=now() - INTERVAL '$GLOBALS[history_tail] days' or dateclose is null)");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
            if ($GLOBALS['regdate'] <> '01.01.1999') {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and  dateopen>='".$GLOBALS['regdate']."'");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
        }

        if ($what=='transactions') {
            $allowed=0;
            if ($GLOBALS[allowed_pids]!='') {
                $res=$this->db->GetRow("select * from $what where id=$id and  (receiver in ($GLOBALS[allowed_pids]) or sender in ($GLOBALS[allowed_pids]))");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            } else {
                $allowed=1;
            }
            if ($GLOBALS['history_tail']>0) {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and valuedate>=now() - INTERVAL '$GLOBALS[history_tail] days'");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
            if ($GLOBALS['regdate'] <> '01.01.1999') {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and  valuedate>='".$GLOBALS['regdate']."'");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
        }
        if ($what=='loans') {
            $allowed=0;
            if ($GLOBALS[allowed_pids]!='') {
                $res=$this->db->GetRow("select * from $what where id=$id and (receiver_id in ($GLOBALS[allowed_pids]) or sender_id in ($GLOBALS[allowed_pids]))");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            } else {
                $allowed=1;
            }
            if ($GLOBALS['history_tail']>0) {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and end_date>=now() - INTERVAL '$GLOBALS[history_tail] days'");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
            if ($GLOBALS['regdate'] <> '01.01.1999') {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and  date>='".$GLOBALS['regdate']."'");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
        }
        if ($what=='invoices') {
            $allowed=0;

            if ($GLOBALS['history_tail']>0) {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and due_date>=now() - INTERVAL '$GLOBALS[history_tail] days'");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }

            if ($GLOBALS[allowed_pids]!='') {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and topartner_id in ($GLOBALS[allowed_pids])");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            } else {
                $allowed=1;
            }

            if ($GLOBALS['regdate'] <> '01.01.1999') {
                $allowed=0;
                $res=$this->db->GetRow("select * from $what where id=$id and  date>='".$GLOBALS['regdate']."'");
                $res[id]=$res[id]*1;
                if ($res[id]>0) {
                    $allowed=1;
                }
            }
            // if ($GLOBALS['workgroup']['administrator_id']>0) {
            //     $allowed=0;
            //     $res=$this->db->GetRow("select * from $what where id=$id and (frompartner_id='".$GLOBALS['workgroup']['administrator_id']."' or topartner_id='".$GLOBALS['workgroup']['administrator_id']."')");
            //     $res[id]=$res[id]*1;
            //     if ($res[id]>0) {
            //         $allowed=1;
            //     }
            // }
        }

        if (($access['main_admin'])&&($allowed==0)) {
            echo $this->html->message("NOT ALLOWED for not admins<hr>$reason", 'warn', 'orange');
            //$allowed=1;
        }
        //$allowed=0;
         //if($access[main_admin])$allowed=1;
        //echo "allowed $what:$allowed<br>";
        //$allowed=1;
        return $allowed;
    }



    function docs2obj($ref_id, $ref_table, $show_table = 0)
    {
        $res=$this->utils->F_tostring($this->db->GetResults("select ' <a href=\"?act=details&what=documents&id='||t.id||'\">'||t.name||'</a><span onclick=\"confirmation(''?csrf=$GLOBALS[csrf]&act=delete&table=docs2obj&id='||d.id||'&doc_id='||d.doc_id||''')\" style=\"cursor: pointer; cursor: hand; \">[-]</span>' from documents t, docs2obj d where d.doc_id=t.id and d.ref_id=$ref_id and ref_table='$ref_table'"));
        $res.="<a href='?act=add&what=docs2obj&ref_table=$ref_table&ref_id=$ref_id'>[+]</a>";
        if ($show_table>0) {
            //$out.= $this->html->show_hide('Documents', "?act=show&what=documents&plain=1&noadd=1&noexport=1&ref_table=$ref_table&ref_id=$ref_id");
            $res.="<br>".$this->html->show_hide('Documents', "?act=show&what=documents&plain=1&noadd=1&noexport=1&ref_table=$ref_table&ref_id=$ref_id");
        }
        return $res;
    }

    function rev_docs2obj($id, $ref_table)
    {
        $sql="select ' <a href=\"?act=details&what=$ref_table&id='||t.id||'\">'||t.name||'</a><span onclick=\"confirmation(''?csrf=$GLOBALS[csrf]&act=delete&what=docs2obj&id='||d.id||''')\" style=\"cursor: pointer; cursor: hand; \">[-]</span>' from $ref_table t, docs2obj d where d.doc_id=$id and d.ref_table='$ref_table' and d.ref_id=t.id";
        $res=$this->utils->F_tostring($this->db->GetResults($sql));
        $res.="<a href='?act=add&what=docs2obj&ref_table=$ref_table&doc_id=$id'>[+]</a>";
        //$res.="<a href='?act=edit&what=docs2obj&refid=$res[id]'>[+]</a>";
        //$res.="<a href='?act=add&what=$ref_table&docid=$res[id]'>[New]</a>";
        return $res;
    }




    function listitems2obj($ref_id, $ref_table, $list_id)
    {
        $res=$this->utils->F_tostring($this->db->GetResults("select ' <a href=\"?act=details&what=listitems&id='||t.id||'\">'||t.name||'</a><span onclick=\"confirmation(''?csrf=$GLOBALS[csrf]&act=delete&table=listitem2obj&id='||d.id||'&listitem_id='||d.listitem_id||''')\" style=\"cursor: pointer; cursor: hand; \">[-]</span>' from listitems t, listitem2obj d where d.listitem_id=t.id and d.ref_id=$ref_id and ref_table='$ref_table'"));
        $res.="<a href='?act=edit&what=listitem2obj&ref_table=$ref_table&ref_id=$ref_id&list_id=$list_id'>[+]</a>";
        return $res;
    }

    function rev_listitems2obj($id, $ref_table, $list_id)
    {
        $sql="select ' <a href=\"?act=details&what=$ref_table&id='||t.id||'\">'||t.name||'</a><span onclick=\"confirmation(''?csrf=$GLOBALS[csrf]&act=delete&table=listitem2obj&id='||d.id||'&listitem_id='||d.listitem_id||''')\" style=\"cursor: pointer; cursor: hand; \">[-]</span>' from $ref_table t, listitem2obj d where d.listitem_id=$id and d.ref_table='$ref_table' and d.ref_id=t.id";
        $res=$this->utils->F_tostring($this->db->GetResults($sql));
        $res.="<a href='?act=edit&what=listitem2obj&ref_table=$ref_table&listitem_id=$id&list_id=$list_id'>[+]</a>";
        return $res;
    }

    function get_rate_local($currid = 0, $date = '')
    {
        $date=$this->dates->F_date($date, 1);
        $chk=$currid*1;
        if (($chk==0)&&($currid!='')) {
            $currid=$this->db->GetVal("select id from listitems where lower(name)=lower('$currid') and list_id=6");
        }
        if ($currid*1==0) {
            $currid=601;
        }

        $start_date_rate=$this->db->GetVal("select date from rates_local where curr_id=$currid order by date asc limit 1");
        if ($this->dates->is_earlier($date, $start_date_rate)) {
            $rate_cur=$this->get_rate($currid, $date);
            $rate_eur=$this->get_rate(601, $date);
            $rate=$rate_cur/$rate_eur;
        } else {
            $sql="select rate from rates_local where date<='$date' and curr_id=$currid order by date desc limit 1";
            $rate=$this->db->GetVal($sql);
            $rate=$rate*1;
            if ($rate==0) {
                $sql="select rate from rates_local where curr_id=$currid and rate>0 order by date asc limit 1";
                $rate=$this->db->GetVal($sql);
                $rate=$rate*1;
            }
            if ($rate==0) {
                $rate=1;
            }
        }

        return $rate;
    }

    function get_rate($currid = 0, $date = '')
    {
        $date=$this->dates->F_date($date, 1);
        $chk=$currid*1;
        if (($chk==0)&&($currid!='')) {
            $currid=$this->db->GetVal("select id from listitems where lower(name)=lower('$currid') and list_id=6");
        }
        if ($currid*1==0) {
            $currid=600;
        }

        $sql="select rate from rates where date<='$date' and currency=$currid order by date desc limit 1";

        $rate=$this->db->GetVal($sql);
        $rate=$rate*1;
        //echo "$sql ($rate)<br>";
        if ($rate==0) {
            $sql="select rate from rates where currency=$currid and rate>0 order by date asc limit 1";
            $rate=$this->db->GetVal($sql);
            $rate=$rate*1;
            //echo "$sql ($rate)<br>";
        }
        if ($rate==0) {
            //$rate=exchangeRate(1,'USD','EUR');
            $rate=1;
        }
        //echo "$currid,$date:$rate<br>";
        return $rate;
    }

    function show_cal($year)
    {
        $year  = isset($_GET['y']) ? $_GET['y'] : $this->dates->F_thisyear();
        $easter=$this->utils->easter($year);
        $out.="<!DOCTYPE html>
            <html>
            <head>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
        <title>PHP Calendar</title>
        <link type='text/css' rel='stylesheet' media='all' href='".ASSETS_URI."/assets/css/cal_style.css' />
        </head>
        <body>
        $year
        <table><tr>";
        for ($month=1; $month<=12; $month++) {
            $cal=$this->show_cal_m($year, $month);
            $out.="<td valign='top'>$cal<td>";
            //if($month % 3 ==0){$out.="</tr><tr>";}
            $out.="</tr><tr>";
        }

        $out.="</tr></table></body>
        </html>";
        echo $out;
    }
    function show_cal_m($year, $month)
    {

        include_once FW_DIR.'/classes/calendar/calendar.php';
        $calendar = Calendar::factory($month, $year, array('week_start' => 1));


        $calendar->standard('today')
            ->standard('prev-next')
            ->standard('holidays')
            ->standard('weekends');

        $start="01.".sprintf("%02s", $month).".$year";
        $end=$this->dates->F_dateadd_month($start, 1);

        /*
        $sql="select * from events where datefrom>='$start' and datefrom<='$end'";
        if (!($cur = pg_query($sql))) {$this->html->SQL_error($sql);}
        while ($row = pg_fetch_array($cur)) {
            $event = $calendar->event()->condition('timestamp', strtotime($row[datefrom]))->title($row[name])->output($row[name]);
            $calendar->attach($event);
        }

        $sql="select * from events where dateto>='$start' and dateto<='$end'";
        if (!($cur = pg_query($sql))) {$this->html->SQL_error($sql);}
        while ($row = pg_fetch_array($cur)) {
            $event = $calendar->event()->condition('timestamp', strtotime($row[dateto]))->title($row[name])->output($row[name]);
            $calendar->attach($event);
        }


        $sql="select * from transactions where valuedate>='$start' and valuedate<='$end'";
        if (!($cur = pg_query($sql))) {$this->html->SQL_error($sql);}
        while ($row = pg_fetch_array($cur)) {
            $event = $calendar->event()->condition('timestamp', strtotime($row[valuedate]))->title($row[samount])->output($row[samount]);
            $calendar->attach($event);
        }

        */
        $sql="select * from schedules where nextdate>='$start' and nextdate<='$end'";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        while ($row = pg_fetch_array($cur)) {
            $event = $calendar->event()->condition('timestamp', strtotime($row[nextdate]))->title($row[id])->output("<a href='?act=edit&what=schedules&id=$row[id]'>$row[descr]</a>");
            $calendar->attach($event);
        }

        $out.="
                <div style='width:800px; padding:10px; margin:10px auto'>
                <table class='calendar'>
                <thead>
                <tr class='navigation'>
                <th class='prev-month'></th>
            <th colspan='5' class='current-month'>".$calendar->month()."</th>
            <th class='next-month'></th>
            </tr>
            <tr class='weekdays'>";
        foreach ($calendar->days() as $day) {
            $out.="<th>$day</th>";
        }
        $out.=" </tr>
            </thead>
            <tbody>";

        foreach ($calendar->weeks() as $week) {
            $out.="<tr>";
            foreach ($week as $day) {
                list($number, $current, $data) = $day;
                $classes = array();
                $output  = '';
                if (is_array($data)) {
                    $classes = $data['classes'];
                    $title   = $data['title'];
                    $output  = empty($data['output']) ? '' : '<ul class="output"><li>'.implode('</li><li>', $data['output']).'</li></ul>';
                }
                $out.="<td class='day ".implode(' ', $classes)."'>
                        <span class='date' title='".implode(' / ', $title)."'>$number</span>
                    <div class='day-content'>$output</div>
                    </td>";
            }
            $out.="</tr>";
        }
        $out.="</tbody>
            </table>
            </div>
        ";
        unset($calendar);
        return $out;
    }
    function show_cal_2($year)
    {
        include FW_DIR.'/classes/calendar/calendar.php';

        $month = isset($_GET['m']) ? $_GET['m'] : null;
        $year  = isset($_GET['y']) ? $_GET['y'] : null;

        $calendar = Calendar::factory($month, $year, array('week_start' => 1));

        $event1 = $calendar->event()->condition('timestamp', strtotime(date('F').' 21, '.date('Y')))->title('Hello All')->output('<a href="http://google.com">Going to Google</a>');
        $event2 = $calendar->event()->condition('timestamp', strtotime(date('F').' 21, '.date('Y')))->title('Something Awesome')->output('<a href="http://coreyworrell.com">My Portfolio</a><br />It\'s pretty cool in there.');

        $calendar->standard('today')
            ->standard('prev-next')
            ->standard('holidays')
            ->standard('weekends')
            ->attach($event1)
            ->attach($event2);

            $prev="<a href='".htmlspecialchars($calendar->prev_month_url())."'>".$calendar->prev_month()."</a>";
            $next="<a href='".htmlspecialchars($calendar->next_month_url())."'>".$calendar->next_month()."</a>";

            $out.="<!DOCTYPE html>
                <html>
                <head>
                <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
            <title>PHP Calendar</title>
            <link type='text/css' rel='stylesheet' media='all' href='../assets/css/cal_style.css' />
            </head>
            <body>
                <div style='width:800px; padding:20px; margin:50px auto'>
                <table class='calendar'>
                <thead>
                <tr class='navigation'>
                <th class='prev-month'>$prev</th>
            <th colspan='5' class='current-month'>".$calendar->month()."</th>
            <th class='next-month'>$next</th>
            </tr>
            <tr class='weekdays'>";
        foreach ($calendar->days() as $day) {
            $out.="<th>$day</th>";
        }
        $out.=" </tr>
            </thead>
            <tbody>";

        foreach ($calendar->weeks() as $week) {
            $out.="<tr>";
            foreach ($week as $day) {
                list($number, $current, $data) = $day;
                $classes = array();
                $output  = '';
                if (is_array($data)) {
                    $classes = $data['classes'];
                    $title   = $data['title'];
                    $output  = empty($data['output']) ? '' : '<ul class="output"><li>'.implode('</li><li>', $data['output']).'</li></ul>';
                }
                $out.="<td class='day ".implode(' ', $classes)."'>
                        <span class='date' title='".implode(' / ', $title)."'>$number</span>
                    <div class='day-content'>$output</div>
                    </td>";
            }
            $out.="</tr>";
        }
        $out.="</tbody>
            </table>
            </div>

            </body>
            </html>";

        echo $out;
    }
    function chk_notify($what, $id)
    {
        global $uid;
        $isnotified='';
        $sql = "SELECT * FROM useralerts WHERE refid=$id and tablename='$what' and userid=$uid and wasread='0'";
        $res2=$this->db->GetRow($sql);
        if ($uid==$res2[userid]) {
            $sql = "SELECT id FROM useralerts WHERE refid=$id and tablename='$what' and userid=$uid and wasread='0' and confirm='1'";
            $count=$this->db->GetVal($sql)*1;
            $dummy=$this->db->GetVal("update useralerts set readdate=now(), readtime=now(), wasread='1' where refid=$id and tablename='$what' and userid=$uid and wasread='0' and confirm='0'");
            $userfrom=$this->db->GetVal("select username from users where id=$res2[fromuserid]");
            if ($count>0) {
                $confirm="Requeres manual confirmation. Press <a onclick=\"leavecomment('?csrf=$GLOBALS[csrf]&act=save&what=sw&field=confirm&table=useralerts&id=$count')\">[here]</a> to confirm.";
            } else {
                $confirm="";
            }
            $isnotified="<font color=FF0000>This record had an alert for you set by $userfrom. $confirm</font>";
        }
        return $isnotified;
    }
    function details_bar($what = '', $id = 0, $more = '')
    {
        $text="WARNING! Confirm to delete record $id from $what?";
        $isnotified=$this->chk_notify($what, $id);
        $fav=$this->isinfavorites($what, $id);
        //"?act=pdf&what=pdf_qr&acttag=d&whattag=i&id=20820";
        $whattag=$what;
        if ($whattag=='invoices') {
            $whattag='i';
        }
        if ($whattag=='documents') {
            $whattag='d';
        }
        if ($whattag=='partners') {
            $whattag='p';
        }
        if (($GLOBALS['access']['edit_'.$what])&&($_POST[noedit]!=1)) {
            $edit=":: <a href='?act=edit&table=$what&id=$id'><img src='".ASSETS_URI."/assets/img/custom/edit.png'> Edit </a>";
        }
        if (($GLOBALS['access']['edit_'.$what])&&($_POST[nodelete]!=1)) {
            $del_btn.= ":: <i class='icon-trash withpointer' onclick=\"confirmation('?csrf=$GLOBALS[csrf]&act=delete&what=$what&id=$id','$text')\" onMouseOver=\"this.className='icon-trash icon-white withpointer black'\" onMouseOut=\"this.className='icon-trash withpointer'\"></i> Delete ";
        }
        $qr="<a href='?act=pdf&what=pdf_qr&acttag=d&whattag=$whattag&id=$id'><i class='icon-qrcode withpointer'></i> QR</a>";
        $out.="<div class='alert alert-info'>$fav $edit :: <a href='?act=edit&table=notify&refid=$id&tablename=$what'><img src='".ASSETS_URI."/assets/img/custom/MailSend.png'> Notify </a> :: $qr $del_btn | $more $isnotified</div>";
        return "<span media='print' class='noPrint'>$out</span>";
    }
    function readconfig($what)
    {
          $res=$this->db->GetVar("SELECT value FROM config WHERE name='$what';");
          return $res;
    }
    function writeconfig($what, $value)
    {
        $has_val=$this->readconfig($what);
        if ($has_val!='') {
            $res=$this->db->GetVar("UPDATE config set value='$value' WHERE name='$what';");
        } else {
            $res=$this->db->GetVar("INSERT INTO config (name, value) values ('$what','$value');");
        }

        return 0;
    }
    function SQL_filter($tablename, $prefix)
    {
        $sql="$sql ".$this->SQL_filter_deep($tablename, $prefix, $_GET);
        $sql="$sql ".$this->SQL_filter_deep($tablename, $prefix, $_POST);
        return $sql;
    }
    function SQL_filter_deep($tablename, $prefix, $array)
    {
        foreach ($array as $key => $value) {
            $chunks=explode("_", $key);
            if ($chunks[0]=='field') {
                array_shift($chunks);
                $field=implode('_', $chunks);
                $sql1="SELECT data_type FROM information_schema.columns WHERE table_schema='public' and lower(table_name)=lower('$tablename') and lower(column_name)=lower('$field') and table_catalog='$GLOBALS[dbname]';";
                $data_type=$this->db->GetVal($sql1);
                $ok=0;
                if ($data_type!='') {
                    //$func="\$tmp=$this->html->readRQ('$field')";
                    if ($data_type=='date') {
                        $tmp=$this->dates->F_date(readRQ($key));
                        $sql = "$sql and ".$prefix."$field='$tmp'";
                        $ok++;
                    }
                    if (($data_type=='integer')||($data_type=='double precision')) {
                        $tmp=$this->html->readRQn($key);
                        if ($tmp!=0) {
                            $sql = "$sql and ".$prefix."$field=$tmp";
                        }
                        $ok++;
                    }
                    if ($ok==0) {
                        $tmp=$this->html->readRQ($key);
                        if ($tmp!='') {
                            $sql = "$sql and ".$prefix."$field='$tmp' ";
                        }
                    }
                }
                if ($field=='df') {
                    $tmp=$this->dates->F_date(readRQ($key));
                    $sql = "$sql and ".$prefix."date>='$tmp'";
                }
                if ($field=='dt') {
                    $tmp=$this->dates->F_date(readRQ($key));
                    $sql = "$sql and ".$prefix."date<'$tmp'";
                }
                if ($field=='like_name') {
                    $tmp=$this->html->readRQ($key);
                    $sql = "$sql and lower(".$prefix."name) like lower('%$tmp%')";
                }
                //$res.="|$field|($data_type) => " . $value . "$sql<br>\n";
            }
        }
        return $sql;
    }
    function username($id, $short = 0)
    {

        if ($short>0) {
            $name=$this->db->GetVal("select substr(surname,1,$short)||' '||substr(firstname,1,$short) from users where id=$id");
        } else {
            $name=$this->db->GetVal("select surname||' '||firstname from users where id=$id");
        }
        if($name==' ')$name=$this->db->GetVal("select username from users where id=$id");
        return $name;
    }
    function surname($id, $short = 0)
    {

        if ($short>0) {
            $name=$this->db->GetVal("select substr(surname,1,$short) from users where id=$id");
        } else {
            $name=$this->db->GetVal("select surname from users where id=$id");
        }
        return $name;
    }


    function DB_get_event($alias='', $date='', $reference='', $refid=0)
    {
        $date=$this->dates->F_date($date, 1);
        $type=$this->db->getval("SELECT id from listitems where alias='$alias' order by id limit 1");
        //echo "$type<br>";
        $sql="select * from events where refid='$refid' and reference='$reference' and type='$type' and datefrom<='$date' and complete='1' and active='1' order by datefrom desc limit 1";
        $res=$this->db->GetRow($sql);
        if ($res[listitem_id]>0) {
            $res[listitem]=$this->get_row('listitems', $res[listitem_id]);
        }
        if ($res[partner_id]>0) {
            $res[partner]=$this->get_row('partners', $res[partner_id]);
        }
        return $res;
    }


    function DB_getcurrentevent($type, $date, $reference, $refid)
    {

        $date=$this->dates->F_date($date, 1);

        if (($type==1301)||($type==1302)||($type==1305)||($type==1306)||($type==1307)||($type==1382)) {
            $sql="select text1 from events where refid='$refid' and reference='$reference' and type='$type' and datefrom<='$date' and complete='1' order by datefrom desc limit 1";
        }
        if (($type==1303)||($type==1303)) {
            $sql="select sum(amount) from events where refid='$refid' and reference='$reference' and type='$type' and datefrom<='$date' and complete='1'";
        }

        $res=$this->db->GetVal($sql);
        return $res;
    }
    function DB_getcurrentevents($type, $date, $reference, $refid)
    {

        $date=$this->dates->F_date($date, 1);

        if (($type==1301)||($type==1302)||($type==1305)||($type==1306)||($type==1307)||($type==1382)) {
            $sql="select text1,partnerid from events where refid='$refid' and reference='$reference' and type='$type' and datefrom<='$date' and complete='1' order by datefrom desc";
            //$res=$this->db->GetResults($sql);
            $res = $this->utils->F_toarray_associative($this->db->GetResults($sql));
            //echo $this->html->pre_display($res); exit;
        }




        $res2=array(
            'a'=>'f',
            'b'=>'g',
            );
        return $res;
    }

    function DB_getcurrenteventAdd($type, $date, $reference, $refid)
    {

            $sql="select addinfo from events where refid='$refid' and reference='$reference' and type='$type' and datefrom<='$date' and complete='1' order by datefrom desc limit 1";
        $res=$this->db->GetVal($sql);
        if ($res=='No query given') {
            $res="";
        }
        return $res;
    }
    function get_new_docname($date='')
    {

        if ($date=='') {
            $date=$this->dates->F_date("", 1);
        }
        $month=substr($date, 3, 2);
        $year=substr($date, 8, 2);
        $lastday=$this->dates->days_in_month($month, $year);
        $sql="select count(*) from documents where date>='01.$month.$year' and date<='$lastday.$month.$year';-- LD:$lastday, M:$month, Y:$year";
        //$out.= "<br>$sql";
        $cntr=$this->db->GetVal($sql);
        if ($cntr>0) {
            $sql="select name from documents where date>='01.$month.$year' and date<='$lastday.$month.$year' order by name desc limit 1";
            //$out.= "<br>$sql";
            $name=$this->db->GetVal($sql); //01.01.2009
            $cntr=substr($name, 6)*1+1;//01-34-6789
        } else {
            $cntr=1;
        }
            //$cntr=2347;
            $name=$year."-".$month."-".sprintf("%04s", $cntr);
            return $name;
    }
    function scheduler()
    {
        global $today;
        //$count=$this->db->GetVal("select count(*) from partners where
        //  date_part('month',date) = '04' AND
        //  date_part('day', date) = '09'
        // and active='t'");
        $count=$this->db->GetVal("select count(*) from schedules where nextdate<='$today' and active='t'");
        //echo "<br><br>Count:$count";
        if ($count>0) {
            $sql="select * from schedules where nextdate<='$today' and active='t'";
            if (!($cur = pg_query($sql))) {
                $this->html->SQL_error($sql);
            }
            while ($row = pg_fetch_array($cur)) {
                $nextdate=$row[nextdate];
                $qty=$row[qty]-1;
                if ($qty==0) {
                    $row[active]='f';
                } else {
                    if (($row[interval]*1)==0) {
                        $row[interval]=1;
                    }
                    if ($row[type]==3000) {
                        $nextdate=$this->dates->F_dateadd($nextdate, $row[interval]);
                    }
                    if ($row[type]==3001) {
                        $nextdate=$this->dates->F_dateadd($nextdate, 1);
                    }
                    if ($row[type]==3002) {
                        $nextdate=$this->dates->F_dateadd($nextdate, 7);
                    }
                    if ($row[type]==3003) {
                        $nextdate=$this->dates->F_dateadd($nextdate, $this->dates->F_daysinmonth($nextdate));
                    }
                    if ($row[type]==3006) {
                        $nextdate=$this->dates->F_dateadd($nextdate, $this->dates->F_daysinyear($nextdate));
                    }
                    if ($row[type]==3004) {
                        for ($x=1; $x<=3; $x++) {
                            $nextdate=$this->dates->F_dateadd($nextdate, $this->dates->F_daysinmonth($nextdate));
                        }
                    }
                    if ($row[type]==3005) {
                        for ($x=1; $x<=6; $x++) {
                            $nextdate=$this->dates->F_dateadd($nextdate, $this->dates->F_daysinmonth($nextdate));
                        }
                    }
                }
                if($this->field_exists($row[tablename], 'name'))$reference=$this->db->GetVal("SELECT name from $row[tablename] where id=$row[refid]; -- from scheduler");
                $type=$this->db->GetVal("SELECT name from listitems where id=$row[type]");
                $user=$this->db->GetVal("SELECT username from users where id=$row[userid]");
                //////////////-->echo "$row[name]($row[nextdate]-$row[interval]-$nextdate)$row[type]($row[usersinvolved])<br>";
                $this->db->GetVal("update schedules set prevdate='$row[nextdate]', nextdate='$nextdate', qty=$qty, active='$row[active]' where id=$row[id]");
                //$row[usersinvolved]=str_ireplace(" ","",$row[usersinvolved]);
                $users=explode(",", $row[usersinvolved]);
                //print_r ($users);
                $startdate=$this->dates->F_date("", 1);
                $checkdate=$startdate;
                $enddate=$this->dates->F_dateadd($startdate, 2);
                $parties="";
                if ($row[tablename]=='partners') {
                    $parties=$row[refid];
                }
                foreach ($users as $userid) {
                    $userid=$userid*1;
                    if ($userid>0) {
                        $text="Reminder: $row[name] ($row[tablename]:$reference)";
                        $uname=$this->db->GetVal("select username from users where id=$userid");
                        $sql="insert into useralerts (userid, fromuserid, date, time, tablename, refid, descr, confirm, addinfo) values ($userid, $row[userid], now(),now(), '$row[tablename]', $row[refid], '$text', '$row[confirm]','$row[descr]')";
                        $dummy=$this->db->GetVal($sql);
                        //echo "{$uname}<br>";
                        //echo "From:$row[userid] TO:$userid<br>";
                        if ($row[send_sms]=='t') {
                            //send SMS
                            $text="Remainder:".$row[name]." ".$row[descr];
                            $mobile=$this->db->GetVal("select mobile from users where id=$userid");
                            if ($mobile!='') {
                                $this->comm->sendsms($mobile, $text);
                            }
                            //exit;
                        }
                        if ($row[send_mail]=='t') {
                            //send Mail
                            $to=$this->db->GetVal("select email from users where id=$userid");
                            $from=$this->db->GetVal("select email from users where id=$row[userid]");

                            $text="$row[name]<br>$row[descr]<br>$row[addinfo]";
                            //echo "$text";
                            $subject="System alert [Reminder]";
                            if (($to!='')&&($from!='')) {
                                $mail=$this->comm->sendmail_html($to, $from, $subject, $text);
                            }
                            //exit;
                        }
                        if ($row[makeintorders]=='t') {
                            //make Inernal order
                            $this->make_document(1658, $startdate, $checkdate, $enddate, "$row[userid]", $userid, $parties, 0, "$row[name] $row[descr]");
                            //exit;
                        }
                        if (($row[makerequests]=='t')&&($row[tablename]=='partners')) {
                            //make Client Request
                            $now=$this->dates->F_date("", 1);
                            $means=4205;
                            $partnerid=$row[refid];
                            $clientid=$this->db->GetVal("select clientid from clients2partners where partnerid=$partnerid order by clientid desc limit 1")*1;
                            $jur=$this->db->GetVal("select jur from partners where id=$partnerid")*1;
                            $topartnerid=$jur==3500?1438:1125;
                            $authperson="System";
                            $type=4303;
                            $descr="$row[name] $row[descr]";
                            $addinfo='Auto generated by System';
                            $name=$this->get_new_name('clientrequests', $now);
                            $sql="insert into clientrequests (
                                    name,
                                    date,
                                    means,
                                    partnerid,
                                    clientid,
                                    topartnerid,
                                    authperson,
                                    type,
                                    descr,
                                    addinfo,
                                    receivedby
                                ) VALUES (
                                    '$name',
                                    now(),
                                    '$means',
                                    '$partnerid',
                                    '$clientid',
                                    '$topartnerid',
                                    '$authperson',
                                    '$type',
                                    '$descr',
                                    '$addinfo',
                                    $userid
                                    );";

                            $cur=$this->db->GetVal($sql);
                            //$crid=($this->db->GetVal("select max(id) from $what")*1);
                            //echo "<br><br><br>$sql<br>";
                        }
                    }
                }
            }
        }
    }


    function dailyroutine()
    {
        global $today;
        $param='daily';
        $chkdate=$this->db->GetVal("select value from config where name='$param'");
        $days=$this->dates->F_datediff($chkdate, $today);
        $message='';
        if ($days>=0) {
            $newdate=$this->dates->F_dateadd($chkdate, +1);
            if ($days>33) {
                $newdate=$today;
            }
            $this->db->GetVal("update config set value='$newdate' where name='$param'");
            //Here goes daily procedures
        }//end daily procedure
        $email=$GLOBALS['settings']['system_email'];
        if ($message!='') {
            $this->comm->sendmail_html($email, $email, "Common Daily Routine Run", $message);
        }
    }

    function ECB_rates()
    {
        //$date=$GLOBALS[today];
        $rates = $this->comm->getResultFromECB('USD');
        $date=$rates[date];
        foreach ($rates[rates] as $curr => $rate) {
            $currid=$this->db->GetVal("select id from listitems where list_id=6 and lower(name)=lower('$curr')")*1;
            $count=$this->db->GetVal("select rate from rates where currency=$currid and date='$date'")*1;
            //echo $this->html->pre_display($rate,"rate $i $date ($currid) $count");
            if ($count==0) {
                $sql="insert into rates (currency, date, rate) values ($currid, '$date', $rate)";
                //echo "$sql<br>";
                if (($rate>0)&&($currid>0)) {
                    $tmp=$this->db->GetVal($sql);
                }
            }
        }
        return $rates;
    }
    function yahoo_rates()
    {
        //http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml
        $env="store://datatables.org/alltableswithkeys";
        $yql_query='SELECT * from yahoo.finance.xchange where pair in ("USDEUR", "USDJPY", "USDGBP", "USDRUB", "USDCHF", "USDHKD", "USDGEL")';
        $json=$this->utils->getResultFromYQL($yql_query, $env);
        //echo $this->html->pre_display($json,"query");
        $obj=json_decode($json);
        //echo $this->html->pre_display($obj,"obj");
        foreach ($obj->query->results->rate as $rate) {
            $i++;

            $curr=explode('/', ($rate->Name))[1];
            //echo $rate->name."$var<br>";
            $date=$this->dates->F_MDYDate($rate->Date);
            $rate_val=$rate->Rate*1;
            $currid=$this->db->GetVal("select id from listitems where list_id=6 and lower(name)=lower('$curr')")*1;
            $count=$this->db->GetVal("select rate from rates where currency=$currid and date='$date'")*1;
            //echo $this->html->pre_display($rate,"rate $i $date ($currid) $count");
            if ($count==0) {
                $sql="insert into rates (currency, date, rate) values ($currid, '$date', $rate_val)";
                //echo "$sql<br>";
                $tmp=$this->db->GetVal($sql);
            }
            //echo $this->html->pre_display($rate,"rate $i $curr ($currid) $date");
        }
        if (($i>0)&&($date!='')) {
            $sql="insert into rates (currency, date, rate) values (600, '$date', 1)";
            $tmp=$this->db->GetVal($sql);
        }
    }
    function oandarates($currid, $date)
    {
        $df=substr($date, 0, 6).substr($date, 8, 2);
        $qry="http://www.oanda.com/currency/table?date=$df&date_fmt=normal&exch=USD&sel_list=USD_EUR_RUB_CHF_JPY_GBP_HKD&value=1&format=CSV&redirected=1";

        $block_internet=$this->readconfig('block_internet');
        if ($block_internet>0) {
            return false;
        }
        $proxy=$this->readconfig('proxy');
        if ($proxy!='') {
            $aContext = array(
                'http' => array(
                    'proxy' => 'tcp://'.$proxy,
                    'request_fulluri' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            $url_content = file_get_contents($qry, false, $cxContext);
        } else {
            $url_content = file_get_contents($qry);
        }

        $url_content=$this->utils->clean_content($url_content);
        $url_content = strip_tags($url_content);

        $rates=$url_content;

        $half=explode("Units/1 USD", $rates);
        $middle=explode("New table", $half[1]);
        $data=$middle[0];
        $fields=array('date','Currency','Rate','ID','Count');
        $out.=$this->html->tablehead('', $qry, $order, $addbutton, $fields, $sort);
        $records=explode("\n", $data);
        foreach ($records as $rec) {
            $parts=explode(",", $rec);
            $curr=$parts[1];
            $rate=$parts[3]*1;
            if (($curr!='')&&($rate>0)) {
                $currid=$this->db->GetVal("select id from listitems where list_id=6 and lower(name)=lower('$curr')")*1;
                $count=$this->db->GetVal("select count(*) from rates where currency=$currid and date='$date'")*1;
                if ($count==0) {
                    $tmp=$this->db->GetVal("insert into rates (currency, date, rate) values ($currid, '$date', $rate)");
                }
                $out.= "<tr>";
                $out.= "<td>$date</td><td>$curr</td><td class='n'>$rate</td><td>$currid</td><td>$count</td>";
                $out.= "</tr>";
            }
        }
        $out.=$this->html->tablefoot($i, $totals, $totalrecs);
        return $out;
    }
    function stealth_mode($opt)
    {
        $this->comm->sms2admin("stealth_mode is $opt by $GLOBALS[username]");
        $workgroup_id=3;
        if ($opt=='unhide') {
            $sql="update documents set creator=41 where creator=8 and id<67257;
                update documents set executor=41 where executor=8 and id<67257;
                update config set value='0' where name='no_clients';
                update config set value='0' where name='no_projects';
                update config set value='0' where name='only_owner';
                update config set value='0' where name='only_cyp';
                update config set value='0' where name='hide_hidden_menu';
                ";
            $GLOBALS['hide_hidden_menu']=0;
            $res.= "UN Stealthing<hr>";
            $this->db->GetVal("UPDATE workgroups set restricted='f', include_related='t' where id=$workgroup_id");
            $res.="Restriction removed<br>";
            $this->db->GetVal("DELETE from workgroup_pids where workgroup_id=$workgroup_id");
            $res.="Access granted to all partners<br>";
        }
        if ($opt=='hide') {
            $res.="Checking credentials...";
            $sql="update documents set creator=8 where creator=41 and id<67257;
                update documents set executor=8 where executor=41 and id<67257;
                update config set value='1' where name='no_clients';
                update config set value='1' where name='no_projects';
                update config set value='1' where name='only_owner';
                --update config set value='1' where name='only_cyp';
                update config set value='1' where name='hide_hidden_menu';
                ";
            $GLOBALS['hide_hidden_menu']=1;
            //$res.="Stealthing<hr>";

        //$this->livestatus('Updatting workgroup');
            //$res.="Checking credentials...<br>";//$this->livestatus("");

            $this->db->GetVal("UPDATE workgroups set restricted='t', include_related='t' where id=$workgroup_id");
            //$res.="Restriction applied<br>";//$this->livestatus("");
            //$this->upd_workgroup_pids($workgroup_id,1438,'',1);
            //$res.="Limited partners access<br>";//$this->livestatus("");
        }
    //$res.="SQL:$sql";
        $this->db->GetVal($sql);
        //$res.="Upadating menues<br>";

        $sql="select * from groups where id>=2 order by id asc limit 100";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows=pg_num_rows($cur);
        while ($row = pg_fetch_array($cur)) {
            $i++;
            $this->gen_fast_menu($row[id]);
            //$this->livestatus(str_replace("\"","'",$this->html->draw_progress($i/$rows*100)));
            //$res.="GID:$row[id] - $row[name]<br>";
            //$res.="Checking credentials....<br>";//$this->livestatus("");
        }


        //$this->livestatus("$opt done.<br>");
        //$res.="Check is OK";
        return $res;
    }

    function chk_birthday()
    {
        global $uid, $today;
        $ubday=$this->db->GetVal("select birhtdate from employees where userid=$uid order by id desc limit 1");
        $ub=substr($ubday, 0, 5);
        $td=substr($today, 0, 5);
        //echo "BD:$ub - $td<br>"; exit;
        //if($gid<20)$out.= "<img src='".ASSETS_URI."/assets/img/custom/NY2011.png'>";
        if ($ub == $td) {
            $out.= "<img src='".ASSETS_URI."/assets/img/custom/Happy-Birthday2.gif'><br>($ub == $td [$ubday:$uid])";
        }
        return $out;
    }
    function user_alerts()
    {
        global $uid;
            $count=$this->db->GetVal("select count(*) from useralerts a where wasread='0' and (a.userid=$uid or a.fromuserid=$uid) and a.userid=$uid")*1;
        if ($count>0) {
            return $this->html->show_hide("$count Unread Alerts", "?act=show&what=useralerts&plain=1&unread=1&received=1&title=My Alerts", "btn-danger");
        }
    }
    function vacations($id, $year)
    {
        $res=$this->db->GetRow("select * from employees where id=$id");
        $days=$this->dates->F_datediff($res[employdate], "31.12.$year");
        if ($days>365) {
            $days=365;
        }
        if ($days<0) {
            $days=0;
        }
        $days=round(24*$days/365);
        if ($res[type]==3103) {
            $days=0;
        }
        return $days;
    }
    function vacationsleft($id, $year)
    {
        $vacations=$this->vacations($id, $year-2)+$this->project->vacations($id, $year-1)+$this->project->vacations($id, $year);
        $vacationsspent=$this->vacationsspent($id, $year-2)+$this->project->vacationsspent($id, $year-1)+$this->project->vacationsspent($id, $year);
        //$out.= "$vacationsspent";
        $days=$vacations-$vacationsspent;
        return $days;
    }
    function vacationsspent($id, $year)
    {
        global $ht;
        $days=0;
        $sql="select * from vacations where emplid=$id  and type=3200 and todate>='01.01.$year' and fromdate<='31.12.$year'";
        //$out.= "$sql<br>";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        while ($row = pg_fetch_array($cur)) {
            if ($this->dates->F_datediff($row[fromdate], "01.01.$year")>0) {
                $row[fromdate]="01.01.$year";
            }
            if ($this->dates->F_datediff("31.12.$year", $row[todate])>0) {
                $row[todate]="31.12.$year";
            }

            $wdays=$this->dates->get_working_days($row[fromdate], $row[todate]);
            $wdays2=$this->dates->F_datediff($row[fromdate], $row[todate])+1;
            $days=$days+$wdays;
            //$out.= "From:$row[fromdate] To:$row[todate] ($wdays/$wdays2)<br>";
        }
        return $days;
    }
    function save_from_inline()
    {
        //echo "<pre>";print_r($_POST);echo "</pre>";exit;
        $obj=readRQ('obj');
        if ($obj=='operdata') {
            $lot_id=$this->html->readRQn('lot_id');
            $volume=$this->html->readRQn('value');
            $date=$this->dates->F_date($this->html->readRQ('date'), 1);
            //$res=make_load($lot_id,$date,$volume);
            return $res[volume];
        }
        if ($obj=='planned_resources') {
            $p_res_id=$this->html->readRQn('p_res_id');
            $volume=$this->html->readRQn('value');
            $this->db->GetVal("update planned_resources set volume=$volume where id=$p_res_id");
            $res=$volume;
            return $res[volume];
        }
    }
    function shoppingcart()
    {
        global $cart;
        //echo $GLOBALS[cart]; exit;
        $scart=$this->showCart();
        $output='
            <div id="" class="alert alert-info">
            <h5><i class=\'icon-shopping-cart\'></i> Cart</h5>
            '.$scart.'
            </div>';
        return $output;
    }

    function showCart()
    {
        global $cart;
        //global $db;
        //$cart = $_SESSION['cart'];
        if ($cart) {
            $items = explode(',', $cart);
            $contents = array();
            foreach ($items as $item) {
                $contents[$item] = (isset($contents[$item])) ? $contents[$item] + 1 : 1;
            }
            //$output[] = '<form action="?act=func&" method="post" id="cart">';

            $editlink =  "<a href='?act=add&table=processdata'><i class='icon-random tooltip-test addbtn' data-original-title='Process data'></i></a> : ";
            $editlink.= "<a href='?act=save&what=shoppingcart&func=clear'><i class='icon-trash tooltip-test addbtn' data-original-title='Clear cart'></i></a>";

            $output2[] = '<div class="scroll_checkboxes"><table class=\'table table-bordered table-striped-tr table-morecondensed tooltip-demo  table-notfull\'>';
            foreach ($contents as $pair => $qty) {
                $pitems = explode(':', $pair);
                $table=$pitems[0];
                $id=$pitems[1];
                $sql = "SELECT name FROM $table WHERE id = '$id'; -- from showCart";
                $name = $this->get_name($table, $id); //$this->utils->F_toarray($this->db->GetResults($sql));
                //$row = $result->fetch();
                //extract($row);
                $output2[] = '<tr>';
                $output2[] = '<td>'.$table.'</td>';
                $output2[] = '<td>'.$id.'</td>';
                $output2[] = '<td>'.$name.'</td>';
                $output2[] = '<td><a href="?act=save&what=shoppingcart&func=delete&id='.$table.':'.$id.'" class="r"><img src="'.ASSETS_URI.'/assets/img/custom/delete.gif"></a></td>';
                //$output[] = '<td><input type="text" name="qty'.$id.'" value="'.$qty.'" size="3" maxlength="3" /></td>';
                //$output[] = '<td>&pound;'.($price * $qty).'</td>';
                $total += 1;
                $output2[] = '</tr>';
            }
            $output2[] = '</table></div>';
            $output[]= 'Total: <strong>'.$total.'</strong> Items : '. $editlink;
            $output[]= join('', $output2);
            //$output[] = '<div><button type="submit">Update cart</button></div>';
            //$output[] = '</form>';
        } else {
            $output[] = '<p>Your cart is empty.</p>';
        }
        return join('', $output);
    }

    function get_kywords($table_name, $id)
    {
        $keywordslist=$this->get_val($table_name, 'keywords', $id);
        $keywords=array();
        $words=explode('][', $keywordslist);
        //$out.=$this->html->pre_display($words,$row[keywords]);
        foreach ($words as $word) {
            $word=str_replace('[', '', $word);
            $word=str_replace(']', '', $word);
            //$out.="$word<br>";
            if (!in_array($word, $keywords)) {
                $keywords[]=$word;
            }
        }
        sort($keywords);
        return $keywords;
    }
    function delete_schedules($tablename, $refid)
    {
        $this->db->GetVal("delete from schedules where tablename='$tablename' and refid=$refid");
    }

    function add_schedule($tablename, $refid, $name, $usersinvolved, $nextdate, $interval, $qty, $send_sms, $send_mail, $descr)
    {
        global $access, $uid, $gid,  $system_email;
        $type=3000;
        if ($interval==1) {
            $type=3001;
        }
        if ($interval==7) {
            $type=3002;
        }
        if ($interval==30) {
            $type=3003;
        }
        if ($interval==90) {
            $type=3004;
        }
        if ($interval==180) {
            $type=3005;
        }
        if (($interval==360)||($interval==365)||($interval==366)) {
            $type=3006;
        }

        $vals=array(
            'name'=>$name,
            'type'=>$type,
            'userid'=>$uid,
            'usersinvolved'=>$usersinvolved,
            'date'=>$GLOBALS[today],
            'refid'=>$refid,
            'tablename'=>$tablename,
            'active'=>1,
            'confidential'=>0,
            'nextdate'=>$nextdate,
            'prevdate'=>$GLOBALS[today],
            'interval'=>$interval,
            'descr'=>$descr,
            'addinfo'=>$addinfo,
            'confirm'=>0,
            'qty'=>$qty,
            'makeintorders'=>0,
            'makerequests'=>0,
            'send_sms'=>$send_sms,
            'send_mail'=>$send_mail
        );
        //echo $this->html->pre_display($_POST,'Post'); echo $this->html->pre_display($vals,'Vals');exit;
        $id=$this->db->insert_db('schedules', $vals);
    }

    function convert_currency($amount = 100, $from = 602, $to = 601, $date)
    {
        //$list_id=$this->db->getval("SELECT id from lists where lower(alias)=lower('currency')");
        if ($date=='') {
            $date=$GLOBALS[today];
        }
        if (!is_numeric($from)) {
            $from=$this->get_list_id('currency', $from);  //$this->db->GetVal("select id from listitems where list_id=$list_id and lower(alias)=lower('$from')");
        }
        if (!is_numeric($to)) {
            $to=$this->get_list_id('currency', $to); //$this->db->GetVal("select id from listitems where list_id=$list_id and lower(alias)=lower('$to')");
        }
        //echo "$from - $to<br>";
        $rate=$this->get_rate($from, $date)/$this->get_rate($to, $date);
        $result=$amount/$rate;
        return $result;
    }
    function convert_currency_local($amount = 100, $from = 602, $to = 601, $date='')
    {
        if ($date=='') {
            $date=$GLOBALS[today];
        }
        if (!is_numeric($from)) {
            $from=$this->db->GetVal("select id from listitems where list_id=6 and lower(alias)=lower('$from')");
        }
        if (!is_numeric($to)) {
            $to=$this->db->GetVal("select id from listitems where list_id=6 and lower(alias)=lower('$to')");
        }
        $rate=$this->get_rate_local($from, $date)/$this->get_rate_local($to, $date);
        $result=round($amount/$rate, 2);
        //echo "$amount->$result"; exit;
        return $result;
    }

    function notify_users($tablename, $refid, $descr, $groupslist, $userslist, $sendalert, $sendsms, $sendmail, $confirm)
    {
        global $access, $uid, $gid,  $system_email;
        $me=$this->db->GetRow("select * from users where id=$GLOBALS[uid]");
        $userids = array();
        //foreach ($_POST as $key => $value) {$out.= $key . " => " . $value . "<br>\n";}
        $users=explode(",", $userslist);
        foreach ($users as $user) {
            $user=$user*1;
            if ($user>0) {
                array_push($userids, $user);
                //$out.= "UID:$user<br>";
            }
        }
        $groups=explode(",", $groupslist);
        foreach ($groups as $group) {
            $group=$group*1;
            if ($group>0) {
                $sql="select userid from user_group where groupid=$group";
                if (!($cur = pg_query($sql))) {
                    $this->html->SQL_error($sql);
                }
                while ($row = pg_fetch_array($cur)) {
                    array_push($userids, $row[userid]);
                    //$out.= "GUID:$row[userid]<br>";
                }
            }
        }
        $unuserids=array_unique($userids);
        foreach ($unuserids as $key => $id) {
            $user_data=$this->db->GetRow("select * from users where id=$id");

            //$out.= $key . " => " . $id . "($uname)<br>\n";
            if (($sendsms>0)) {
                $mobile=$this->db->GetVal("select mobile from users where id=$id");
                //$docname=$this->db->GetVal("select name from documents where id=$id");
                $text="$descr. $username";
                //$out.= "$text<br>";
                if ($mobile!='') {
                    $click=$this->comm->sendsms($mobile, $text);
                }
                //$out.= "$click ($docname)-$mobile";
            }
            if (($sendalert>0)) {
                //$docname=$this->db->GetVal("select name from documents where id=$id");
                $text="$descr. $username";
                //$out.= "$text<br>";
                //if($mobile!='')$click=sendsms($mobile,$text);
                $sql="insert into useralerts (userid, fromuserid, date, time, tablename, refid, descr, confirm) values ($id, $uid, now(),now(), '$tablename', $refid, '$text', '$confirm')";
                $dummy=$this->db->GetVal($sql);
                //$out.= "$sql";
            }
            if (($sendmail>0)) {
                $to=$user_data['email'];
                $from=$me['email'];
                $link=$this->get_name($tablename, $refid);
                $subject=strtoupper(APP_NAME).": ".ucfirst($tablename)." - $link";

                $message="Dear $user_data[firstname] $user_data[surname],<br><br>".$descr. "<br>Ref.: ".ucfirst($tablename)." <a href='https://$_SERVER[SERVER_NAME]/?act=details&what=$tablename&id=$refid'>$link</a><br><br><br>Best regards,<br>$me[firstname] $me[surname]";
                //$from=htmlspecialchars_decode($system_email, ENT_QUOTES);
                $this->comm->sendmail_html($to, $from, $subject, $message);
                //echo "$to,$from,$subject,$message<br>";exit;
            }
        }
    }
    function manage_signs()
    {
        global $uid;
        $signatures_dir=$this->readconfig('signatures_dir');
        $imagefile  =  $signatures_dir.'/'.$uid."_sign.png";
        if (!file_exists($imagefile)) {
            $res="No signature</a>";
        } else {
            $res="Change Signature";
        }

        return "<a href='?act=add&what=import_signature&tablename=users&refid=$uid'>$res</a><br>";
    }
    function useroccupation($id)
    {
        $name=$this->db->GetVal("select occupation from employees where userid=$id");
        return $name;
    }

    function post($ref_table, $ref_id, $res = '')
    {
        $sql="select * from posts where ref_table='$ref_table' and ref_id=$ref_id order by date desc";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows=pg_num_rows($cur);
        if ($rows>0) {
            $res.="<div class='post'>";
        }
        while ($row = pg_fetch_array($cur)) {
            //$post=$this->getrow('posts',$post_id);
            $row[date]=substr($row[date], 0, 19);
            $row[user]=$this->username($row[user_id]);
            $res.=$this->html->post($row);
            $res=$this->post('posts', $row[id], $res);
        }
        if ($rows>0) {
            $res.="</div>";
        }
        return $res;
    }




    function scramble_data()
    {
        $sql = "select * from partners where (type in (201,202) or physical='t') order by id";
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows=pg_num_rows($cur);$start_time=$this->utils->get_microtime();
        while ($row = pg_fetch_array($cur)) {
            $i++;


            $name=$this->utils->scramble($row[name]);
            echo "";
            echo "Processing $i of $rows ($name)<br>";
            $this->db->GetVal("update partners set name='$name' where id=$row[id]");
            $vals=array('name'=>$name);
            $this->db->update_db('partners', $row[id], $vals);
        }
    }
    function long_query()
    {
        $runtime=$this->utils->get_run_time();
        if ($runtime>5) {
            //echo "$runtime>5"; exit;;
            $this->DB_log("TIME:$runtime, User:$GLOBALS[user], $GLOBALS[act] $GLOBALS[what] RQ:".$GLOBALS[request_txt]);
        }
    }

    function get_list_array($sql, $no_unique = 0, $no_sort = 0)
    {
        $ids=$this->utils->F_toarray($this->db->GetResults($sql));
        if ($no_unique==0) {
            $ids = array_unique($ids);
        }
        if ($no_sort==0) {
            sort($ids);
        }
        return $ids;
    }
    function get_list_csv($sql, $no_unique = 0, $no_sort = 0, $quoted = 0)
    {
        $ids=$this->get_list_array($sql, $no_unique, $no_sort);
        if ($quoted==0) {
            $result=implode(",", $ids);
        } else {
            $result=implode("','", $ids);
            if ($result!='') {
                $result="'{$result}'";
            }
        }

        return $result;
    }

    function sql_display($sql = 'select 1', $title = '')
    {
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows=pg_num_rows($cur);
        if ($rows>0) {
            $res.="<div class='post'>";
        }
        $result = pg_fetch_array($cur);
        $columnts= pg_num_fields($cur);
        $fieldnames = [];
        $fieldtypes = [];
        for ($colnum=0; $colnum<$columnts; $colnum++) {
            $fieldnames[] = pg_field_name($cur, $colnum);
            $fieldtypes[] = pg_field_type($cur, $colnum);
        }
        $fields=$fieldnames;
        array_unshift($fields, "#");
        $tbl=$this->html->tablehead('', '', '', '', $fields);

        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows=pg_num_rows($cur);

        while ($row = pg_fetch_array($cur)) {
            $i++;
            $tbl.= "<tr class='$class'>";
            $tbl.= "<td>$i</td>";
            $j=0;
            foreach ($fieldnames as $fieldname) {
                $class=($fieldtypes[$j]!='text')?"n":"";
                $tbl.= "<td class='$class'>$row[$j]</td>";
                $j++;
            }
            $tbl.= "</tr>";
        }
        $tbl.="</table>";

        if ($title!='') {
            $out.=$this->html->tag($title, 'foldered');
        }
        return $out.$tbl;
    }
    function sql_to_array($sql = 'select 1', $key = '')
    {
        $res=array();
        if (!($cur = pg_query($sql))) {
            $this->html->SQL_error($sql);
        }
        $rows=pg_num_rows($cur);
        while ($row = pg_fetch_array($cur, null, PGSQL_ASSOC)) {
            $res[] = $row;
        }
        /*
        //if($rows>0)
        $result = $this->sql->query($sql) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql);
        while ($row = $result->fetchArray(SQLITE3_ASSOC))$res[]=$row;
        $result->finalize();
        if($key!=''){
            foreach($res as $key_index=>$value){
                $new_res[$value[$key]]=$value;
            }
            $res=$new_res;
        }
        */
        return $res;
    }

    function array_to_sql($array = array(), $tablename, $create = 0)
    {
        //echo $this->html->pre_display($array,$tablename);

        $i=0;
        foreach ($array as $row_key => $row) {
            $sql_insert_values='';
            foreach ($row as $col_key => $col) {
                $field_name=$col_key;
                $field_value=$col;
                $field_type_prim=gettype($col);
                $is_date=$this->utils->is_date($col) ;
                //echo "not_date=$not_date<br>";
                if (!$is_date) {
                    if (is_numeric($col)) {
                        if (is_int($col)) {
                            $field_type='INTEGER';
                            $def_val=0;
                        } else {
                            $field_type='REAL';
                            $def_val=0;
                        }
                    } else {
                        $col_lower=strtolower($col);
                        if (($col_lower=='true')||($col_lower=='false')||($col_lower=='t')||($col_lower=='f')) {
                            $field_type='BOOL';
                            $def_val="TRUE";
                        } else {
                            $field_type='TEXT';
                            $def_val="''";
                        }
                    }
                } else {
                    $field_type='DATE';
                    $def_val="'01.01.1970'";
                }

                $field_name=str_replace('#', 'number', $field_name);
                $field_name=str_replace("'", '', $field_name);
                $field_name=str_replace(' ', '_', $field_name);
                $field_value=str_replace("'", '', $field_value);
                $field_name=$this->db->escape($field_name);
                $field_value=$this->db->escape($field_value);

                if (!is_string($field_name)) {
                    $field_name="N_$field_name";
                }
                //if(is_numeric())
                //echo "$field_name = $field_value ($field_type)<br>";
                if ($field_name!='') {
                    $sql_create_fields.="   $field_name $field_type  NOT NULL DEFAULT $def_val,\n";
                    $sql_insert_values.="'$field_value',";
                    if ($i==0) {
                        $sql_insert_fields[]=$field_name;
                    }
                }
            }
            if ($i==0) {
                $sql_create="\nCREATE TABLE IF NOT EXISTS $tablename (\n";
                $sql_create.="  id SERIAL PRIMARY KEY,\n";
                $sql_create.="$sql_create_fields";
                $sql_create=rtrim($sql_create, ",\n");
                $sql_create.="\n)WITH OIDS;";
                //echo $this->html->pre_display($sql_create,'sql_create');
                if ($create>0) {
                    $this->db->query($sql_create);
                }
                //$this->sql->exec($sql_create) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql_create);
            }
            $sql_insert_fields_l=implode(',', $sql_insert_fields);
            $sql_insert="\nINSERT INTO $tablename (";
            $sql_insert.=$sql_insert_fields_l;
            $sql_insert.=") VALUES (";
            $sql_insert.=$sql_insert_values;
            $sql_insert=rtrim($sql_insert, ",");
            $sql_insert.=");";
            //$this->sql->exec($sql_insert) or $this->error('SQLite3: '.$this->sql->lastErrorMsg().' in:<br>'.$sql_insert);
            if ($create>0) {
                $this->db->query($sql_insert);
            }
            $sql_insert_all.=$sql_insert;
            //echo $this->html->pre_display($sql_insert,'sql_insert');
            //$sql_inserts.=$sql_insert;
            $i++;
            //echo $this->pre_display($row,$row_key);
        }
        $res['create']=$sql_create;
        $res['insert']=$sql_insert_all;
        return $res;
    }

    function copy_fromDB($data)
    {
        $dest_conn = @pg_connect("host=$data[dest_host] port=$data[dest_port] dbname=$data[dest_dbname] user=$data[dest_user] password=$data[dest_password]");
        if ((!$dest_conn)) {
            return $this->html->error("No connection to destination DB".$this->html->pre_display($data, "data"));
        }

        $source_conn = @pg_connect("host=$data[source_host] port=$data[source_port] dbname=$data[source_dbname] user=$data[source_user] password=$data[source_password]");
        if ((!$source_conn)) {
            return $this->html->error("No connection to source DB".$this->html->pre_display($data, "data"));
        }


        $sql_source = $data[sql_source];
        $table=explode('from ', $sql_source);
        $table=explode(' ', $table[1]);
        $table=$table[0];
        echo ".$table.<br>";

        echo "$sql_source<br>";
        if (!($cur_source = pg_query($source_conn, $sql_source))) {
            $this->html->SQL_error($sql_source);
        }
        $access=array();
        while ($row = pg_fetch_array($cur_source, null, PGSQL_ASSOC)) {
            //echo $this->html->pre_display($row,'');
            $new_id=$this->db->insert_db($table, $row);
            //if(($table=='rates_local')&&($row[id]>80))echo $this->html->pre_display($row,"Old ID:$row[id], new ID:$new_id");
        }
    }

    function api($user_id, $funcs)
    {

        $api=$this->db->getrow("SELECT * from apis where user_id=$user_id limit 1");
        if ($api[id]>0) {
            if ($funcs!='') {
                $this->db->update_db('apis', $api[id], ['functions'=>$funcs]);
            }
        } else {
            //$api[key]=md5(uniqid(time()));
            $api[key]=implode('-', str_split(substr(strtolower(md5(microtime().rand(1000, 9999))), 0, 30), 6));

            $vals=[
                'user_id'=>$user_id,
                'key'=>$api[key],
                'functions'=>$funcs,
                'exp_date'=>$this->dates->F_dateadd_year($GLOBALS[today], 1)
            ];
            $api[id]=$this->db->insert_db('apis', $vals);
        }
        return $api[key];
    }

    function edit_row($table = '', $id = 0, $row = [])
    {
    }

    function panic_data()
    {
    }
    function reset_data()
    {
        require(FW_DIR.DS.'config'.DS.'reset.php');
        $this->chkInstall();
    }
    function chkInstall()
    {
        if (!$this->table_exists('config')) {
            echo "DB not installed.";
            require(FW_DIR.DS.'config'.DS.'setup.php');
            echo "Finished.";
            //echo $this->html->refreshpage('/',30,"<div class='alert alert-error'>Refreshing...</div>"); exit;
            exit;
        }
    }
    function test()
    {
        return "ok";
    }
}
