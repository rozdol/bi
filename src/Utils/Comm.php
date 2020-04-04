<?php
namespace Rozdol\Utils;

use Rozdol\Html\Html;
use Rozdol\Utils\Utils;
use Rozdol\Dates\Dates;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

//use \Sendgrid\Sendgrid;

class Comm
{
    private static $hInstance;

    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new Comm();
        }
        return self::$hInstance;
    }

    public function __construct()
    {
        $this->dates = Dates::getInstance();
        $this->html = Html::getInstance();
        $this->utils = Utils::getInstance();
    }

    public function send_mail_aws($sender_email = '', $recipient_emails, $subject = 'email', $html_body = '', $plaintext_body='',$attachments = []){

        $data_dir=getenv('DATA_DIR');
        putenv("HOME=$data_dir");
        $SesClient = new SesClient([
            'profile' => 'default',
            'version' => '2010-12-01',
            'region'  => getenv('AWS_REGION')
        ]);
        $char_set = 'UTF-8';

        try {
            $result = $SesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => $recipient_emails,
                ],
                'ReplyToAddresses' => [$sender_email],
                'Source' => $sender_email,
                'Message' => [
                  'Body' => [
                      'Html' => [
                          'Charset' => $char_set,
                          'Data' => $html_body,
                      ],
                      'Text' => [
                          'Charset' => $char_set,
                          'Data' => $plaintext_body,
                      ],
                  ],
                  'Subject' => [
                      'Charset' => $char_set,
                      'Data' => $subject,
                  ],
                ],
                // If you aren't using a configuration set, comment or delete the
                // following line
                //'ConfigurationSetName' => $configuration_set,
            ]);
            $messageId = $result['MessageId'];
            return $messageId;
        } catch (AwsException $e) {
            return $e->getAwsErrorMessage();
            // output error message if fails
            //echo $this->html->message($e->getMessage());
            //$this->html->error("The email was not sent. Error message: ".$e->getAwsErrorMessage());
        }
    }
    public function sendgrid_file($from = 'name:name@example.com', $to = 'name:name@example.com', $subject = 'email', $body = '', $attachments = [])
    {
        $bits_from=explode(":", $from);
        $bits_to=explode(":", $to);
        $plain_from=$bits_from[1];
        $plain_to=$bits_to[1];

        $source_file= APP_DIR . DS .'helpers'. DS .'mail.html';
        if (file_exists($source_file)) {
            $html=file_get_contents($source_file);
            $html = str_replace("<%body%>", $body, $html);
            $html = str_replace("<%subscription_id%>", $subscription_id, $html);
            $html = str_replace("<%site_url%>", $GLOBALS[URL], $html);
            $html = str_replace("<%brand_name%>", $GLOBALS[settings][brand_name], $html);
        }

        $email = new \SendGrid\Mail\Mail();
        $email->setFrom($bits_from[1], $bits_from[0]);
        $email->setSubject($subject);
        $email->addTo($bits_to[1], $bits_to[0]);
        //$email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html",
            $html
        );
        foreach ($attachments as $attachment) {
            $file_name=basename($attachment);
            $file_encoded = base64_encode(file_get_contents($attachment));
            $mime="application/text";
            $ext = strtolower(substr(strrchr($file_name, "."),1));
            if($ext=='pdf')$mime="application/pdf";
            if(($ext=='doc')||($ext=='docx'))$mime="application/msword";
            if(($ext=='xls')||($ext=='xlsx'))$mime="application/vnd.ms-excel";
            if(($ext=='zip')||($ext=='rar'))$mime="application/zip";
            $email->addAttachment(
                $file_encoded,
                $mime,
                "$file_name",
                "attachment"
            );
        }
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $response = $sendgrid->send($email);
            // echo $this->html->pre_display($response->statusCode(), "statusCode");
            // echo $this->html->pre_display($response->headers(), "headers");
            // echo $this->html->pre_display($response->body(), "body");
            return 1;
        } catch (Exception $e) {
            return 'Caught exception: '.  $e->getMessage();
        }
    }

    public function sendgrid($from = 'name:name@example.com', $to = 'name:name@example.com', $subject = 'email', $body = '', $subscription_id = 'OneTimeLetter')
    {
        $bits_from=explode(":", $from);
        $bits_to=explode(":", $to);
        $plain_from=$bits_from[1];
        $plain_to=$bits_to[1];

        $source_file= APP_DIR . DS .'helpers'. DS .'mail.html';
        if (file_exists($source_file)) {
            $html=file_get_contents($source_file);
            $html = str_replace("<%body%>", $body, $html);
            $html = str_replace("<%subscription_id%>", $subscription_id, $html);
            $html = str_replace("<%site_url%>", $GLOBALS[URL], $html);
            $html = str_replace("<%brand_name%>", $GLOBALS[settings][brand_name], $html);
        }

        $email = new \SendGrid\Mail\Mail();
        $email->setFrom($bits_from[1], $bits_from[0]);
        $email->setSubject($subject);
        $email->addTo($bits_to[1], $bits_to[0]);
        //$email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html",
            $html
        );
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $response = $sendgrid->send($email);
            // echo $this->html->pre_display($response->statusCode(), "statusCode");
            // echo $this->html->pre_display($response->headers(), "headers");
            // echo $this->html->pre_display($response->body(), "body");
            return 1;
        } catch (Exception $e) {
            return 'Caught exception: '.  $e->getMessage();
        }
    }

    public function sendgrid2($from = 'name:name@example.com', $to = 'name:name@example.com', $subject = 'email', $body = '', $subscription_id = 'OneTimeLetter')
    {
        $apiKey = getenv('SENDGRID_API_KEY');

        $bits=explode(":", $from);
        $from = new \SendGrid\Email($bits[0], $bits[1]);
        $plain_from=$bits[1];
        $bits=explode(":", $to);
        $to = new \SendGrid\Email($bits[0], $bits[1]);
        $plain_to=$bits[1];
        //echo $this->html->pre_display($from,"result $plain_to");
        $source_file= APP_DIR . DS .'helpers'. DS .'mail.html';
        if (file_exists($source_file)) {
            $html=file_get_contents($source_file);
            $html = str_replace("<%body%>", $body, $html);
            $html = str_replace("<%subscription_id%>", $subscription_id, $html);
            $html = str_replace("<%site_url%>", $GLOBALS[URL], $html);
            $html = str_replace("<%brand_name%>", $GLOBALS[settings][brand_name], $html);
        }
        $content = new \SendGrid\Content("text/html", $html);

        $mail = new \SendGrid\Mail($from, $subject, $to, $content);
        $sg = new \SendGrid($apiKey);
        $response = $sg->client->mail()->send()->post($mail);
        $status = $response->statusCode();
        if ($status==202) {
            $status= 1;
        } else {
            //echo "$to,$from,$subject,$description,$body<br>";
            $this->send_announcement_mail($plain_to, $plain_from, $subject, $description, $body);
            //$status= $this->html->pre_display($response,"ERROR");
        }
        return $status;
    }

    public function preparehtmlmail($html)
    {

        preg_match_all('~<img.*?src=.([\/.a-z0-9:_-]+).*?>~si', $html, $matches);
        $i = 0;
        $paths = array();

        foreach ($matches[1] as $img) {
            $img_old = $img;

            if (strpos($img, "http://") == false) {
                $uri = parse_url($img);
                $paths[$i]['path'] = $_SERVER['DOCUMENT_ROOT'].$uri['path'];
                $content_id = md5($img);
                $html = str_replace($img_old, 'cid:'.$content_id, $html);
                $paths[$i++]['cid'] = $content_id;
            }
        }


        //$from_user = "=?UTF-8?B?".base64_encode(DEFCALLBACKMAIL)."?=";
        $from_user = DEFCALLBACKMAIL;

        $boundary = "--".md5(uniqid(time()));
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .="Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $headers .= "From: $from_user\r\n";
        $headers .= "Return-Path:: $from_user\r\n";
        $multipart = '';
        $multipart .= "--$boundary\r\n";
        $kod = 'UTF-8';
        $multipart .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
        $multipart .= "Content-Language: ru\r\n";
        //$multipart .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        //$multipart .= "Content-Transfer-Encoding: Quot-Printed\r\n\r\n";
        //$multipart .= 'Content-Type: text/HTML; charset=ISO-8859-1' . "\r\n";
        $multipart .= 'Content-Transfer-Encoding: 7bit'. "\r\n\r\n";


        $multipart .= "$html\r\n\r\n";

        //'Content-Type: text/html; charset="UTF-8";',
        //'Content-Transfer-Encoding: 7bit',

        //echo $this->html->pre_display($multipart,'multipart');

        //echo $this->html->pre_display($paths,'paths');

        foreach ($paths as $path) {
            if (file_exists($path['path'])) {
                $fp = fopen($path['path'], "r");
            }
            if (!$fp) {
                //return false;
            }

            $imagetype = substr(strrchr($path['path'], '.'), 1);
            $file = fread($fp, filesize($path['path']));
            fclose($fp);

            $message_part = "";

            switch ($imagetype) {
                case 'png':
                case 'PNG':
                    $message_part .= "Content-Type: image/png";
                    break;
                case 'jpg':
                case 'jpeg':
                case 'JPG':
                case 'JPEG':
                    $message_part .= "Content-Type: image/jpeg";
                    break;
                case 'gif':
                case 'GIF':
                    $message_part .= "Content-Type: image/gif";
                    break;
            }

            $message_part .= "; file_name = \"$path\"\r\n";
            $message_part .= 'Content-ID: <'.$path['cid'].">\r\n";
            $message_part .= "Content-Transfer-Encoding: base64\r\n";
            $message_part .= "Content-Disposition: inline; filename = \"".basename($path['path'])."\"\r\n\r\n";
            $message_part .= chunk_split(base64_encode($file))."\r\n";
            $multipart .= "--$boundary\r\n".$message_part."\r\n";
        }

        $multipart .= "--$boundary--\n";
        return array('multipart' => $multipart, 'headers' => $headers);
    }

    public function send_attachment_mail($from = 'it@example.com', $to = 'email@example.com', $subject = 'File', $body = ' ', $attachments = [])
    {
        require_once FW_DIR.DS.'classes/PHPMailer/PHPMailerAutoload.php';
        if($body=='')$body=' ';
        $mail = new \PHPMailer;
        $mail->isSendmail();
        $mail->setFrom($from, '');
        $mail->addReplyTo($from, '');
        $mail->addAddress($to, '');
        $mail->Subject = $subject;
        $mail->Body      = $body;

        foreach ($attachments as $attachment) {
            if(file_exists($attachment)){
                $file_name=basename($attachment);
                $mail->addAttachment($attachment, $file_name);
            }else{
                echo "File $file_name not found;<br>";
            }

        }

        if (!$mail->send()) {
            //echo $this->html->pre_display($mail, "result");
            echo "Mailer Error: " . $mail->ErrorInfo."<br>";
            echo "To:$to<br>";
            echo "From:$from<br>";
            echo "Subject:$subject<br>";
            echo "body:$body<br>";
            echo $this->html->pre_display($attachments,"attachments");
        } else {
            echo "Message '$subject' sent from $from to $to!";
        }
        unset($mail);
    }

    public function send_announcement_mail($to = 'email@example.com', $from = 'it@example.com', $subject = 'Announcement', $description = '', $body = '', $attachments = [])
    {
        require_once FW_DIR.DS.'classes/PHPMailer/PHPMailerAutoload.php';

        $mail = new \PHPMailer;
        $mail->isSendmail();
        $mail->setFrom($from, '');
        $mail->addReplyTo($from, '');
        $mail->addAddress($to, '');
        $mail->Subject = $subject;

        //$source_file= DATA_DIR . DS .'templates'. DS .'email2.html';
        $source_file= APP_DIR . DS .'helpers'. DS .'email.html';
        if(!file_exists($source_file))die("Template $source_file does not exist.");
        $html=file_get_contents($source_file);
        $final_msg = $html;

        $body = str_replace("\n", "</p><p>", $body);

        $final_msg = str_replace("{{SUBJ}}", $subject, $final_msg);
        $final_msg = str_replace("{{DESCR}}", $description, $final_msg);
        $final_msg = str_replace("{{BODY}}", $body, $final_msg);
        $final_msg = str_replace("<%body%>", $body, $final_msg);

        $mail->msgHTML($final_msg);
        $mail->AltBody = $description."\n\n\n".$body;
        //echo $this->html->pre_display($mail,"result");

        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment);
        }

        $mail->AddEmbeddedImage('assets/img/logo_480x40.png', 'logo_2u');
        //send the message, check for errors
        if (!$mail->send()) {
            echo $this->html->pre_display($mail, "result");
            echo "Mailer Error: " . $mail->ErrorInfo;
            unset($mail);
            return "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent  $to!";
            unset($mail);
            return "Message sent  $to";

        }

    }

    public function send_announcement($to = 'email@example.com', $from = 'it@example.com', $subject = 'Announcement', $description = '', $body = '')
    {
        define("DEFCALLBACKMAIL", $from); // WIll be shown as "from".


        //source_file= DATA_DIR . DS .'templates'. DS .'email.html';
        $source_file= APP_DIR . DS .'helpers'. DS .'email.html';
        if (file_exists($source_file)) {
            $html=file_get_contents($source_file);
            //echo "$source_file:$html<br>"; exit;
            $final_msg = $this->preparehtmlmail($html); // give a public function your html*

            $body = str_replace("\n", "</p><p>", $body);

            $final_msg = str_replace("{{SUBJ}}", $subject, $final_msg);
            $final_msg = str_replace("{{DESCR}}", $description, $final_msg);
            $final_msg = str_replace("{{BODY}}", $body, $final_msg);
        } else {
            $final_msg="$subject </p><p>$description </p><p>$body";
            $final_msg = $this->preparehtmlmail($final_msg); // give a public function your html*
            $final_msg = str_replace("\n", "</p><p>", $final_msg);
        }
        //echo DATA_DIR;



        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        //$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        //$final_msg['multipart'] = "=?UTF-8?B?".base64_encode($final_msg['multipart'])."?=";
        if (mail($to, $subject, $final_msg['multipart'], $final_msg['headers'])) {
            return "Email successfully sent to $to<hr>";//.$final_msg['multipart'];
        } else {
            return "Email not sent to $to";
        }
    }
    public function send_announcement2($to = 'email@example.com', $from = 'it@example.com', $subject = 'Announcement', $description = '', $body = '')
    {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        $headers  .= "From: $from\r\n";
            //options to send to cc+bcc
            //$headers .= "Cc: [email]maa@p-i-s.cXom[/email]\r\n";
            //$headers .= "Bcc: [email]email@maaking.cXom[/email]\r\n";
        $source_file= DATA_DIR . DS .'templates'. DS .'email.html';
        //$source_file= TMPLTS_DIR;
        $message = file_get_contents($source_file);
        ;
        echo $message;
        //mail($to, $subject, $message, $headers);

        return true;
    }
    public function mail2admin($subject, $msg)
    {
        $subject=$GLOBALS['db_name'].": $subject";
        $email=$GLOBALS['settings']['admin_mail'];
        if ($email=='') {
            $email=$GLOBALS['settings']['system_email'];
        }
        if ($email=='') {
            $email='it@example.com';
        }
        $mail_text=$msg;
        $mail_text.="<hr>";
        $mail_text.="<b>APP:</b>".$GLOBALS['db_name'].'<hr>';
        $mail_text.="<b>IP:</b>".$GLOBALS['ip'].'<hr>';
        $mail_text.="<b>User:</b>".$GLOBALS['username'].'<hr>';

        $mail_text.=$this->html->pre_display($_GET, 'GET').'<hr>';
        $mail_text.=$this->html->pre_display($_POST, 'POST').'<hr>';
        $mail_text.=$this->html->pre_display($_REQUEST, 'REQUEST').'<hr>';

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


    public function sms2admin($text)
    {
        $mobile=$GLOBALS[admin_tel];
        if ($mobile!='') {
            $click=$this->sendsms($mobile, $text);
        }
    }
    public function sendsms($number, $text)
    {
        //$smsQuery = $text;
        $smsQuery = urlEncode($text);
        $number = urlEncode($number);
        $clickatel_user=$GLOBALS['clickatel_user'];
        $clickatel_pass=$GLOBALS['clickatel_pass'];
        $clickatel_api_id=$GLOBALS['clickatel_api_id'];

        if ($clickatel_user=='') {
            $clickatel_user = getenv('CLICKATEL_USER');
            $clickatel_pass = getenv('CLICKATEL_PASS');
            $clickatel_api_id = getenv('CLICKATEL_API_ID');
        }

        $qry="http://api.clickatell.com/http/sendmsg?user=$clickatel_user&password=$clickatel_pass&api_id=$clickatel_api_id&to=$number&text=".$smsQuery;
        $block_internet=$GLOBALS['block_internet'];
        if ($block_internet>0) {
            return false;
        }
        $proxy=$GLOBALS['proxy'];
        if ($proxy!='') {
            $aContext = array(
                'http' => array(
                    'proxy' => 'tcp://'.$proxy,
                    'request_fulluri' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            $data = file_get_contents($qry, false, $cxContext);
        } else {
            $data = file_get_contents($qry);
        }
        $data=$this->utils->clean_content($data);
        //DB_log("SENT SMS:$number:$text");
        return $data;
    }

    public function weather($ll = "42.73,133.08")
    {
        $today=$this->F_date("", 1);
        $qry="http://free.worldweatheronline.com/feed/weather.ashx?q=$ll&format=json&num_of_days=5&key=06007d0767093846130302";

        //$proxy='10.107.24.11:3128';

        $block_internet=$GLOBALS['settings']['block_internet'];
        if ($block_internet>0) {
            return false;
        }
        $proxy=$GLOBALS['settings']['proxy'];
        if ($proxy!='') {
            $aContext = array(
                'http' => array(
                    'proxy' => 'tcp://'.$proxy,
                    'request_fulluri' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            $data = file_get_contents($qry, false, $cxContext);
        } else {
            $data = file_get_contents($qry);
        }
        $page=$this->utils->clean_content($page);
        $json = strip_tags($data);
        $res = json_decode($json);
        $current=$res->data->current_condition[0]->weatherDesc[0]->value;
        $temp_C=$res->data->current_condition[0]->temp_C;
        //echo "Today:$current<br>";
        $arr['today']="$temp_C °C $current";
        $arr[$today]=$arr['today'];
        $arr['today']="Weather today: ".$arr['today'];
        foreach ($res->data->weather as $details) {
            $desc=$details->weatherDesc[0]->value;
            $date=F_date($details->date);
            $max=$details->tempMaxC;
            $min=$details->tempMinC;
            $arr[$date]="$min °C, $max °C, $desc";
            //echo "$date: $min, $max, $desc<br>";
        }
        return $arr;
    }

    public function exchangeRate($amount, $currency, $exchangeIn)
    {
        require_once 'JSON.php';
        $url = @ 'http://www.google.com/ig/calculator?hl=en&q=' . urlEncode($amount . $currency . '=?' . $exchangeIn);

        $block_internet=$this->data->readconfig('block_internet');
        if ($block_internet>0) {
            return false;
        }
        $proxy=$this->data->readconfig('proxy');
        if ($proxy!='') {
            $aContext = array(
                'http' => array(
                    'proxy' => 'tcp://'.$proxy,
                    'request_fulluri' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            $data = @ file_get_contents($url, false, $cxContext);
        } else {
            $data = @ file_get_contents($url);
        }
        $data=$this->utils->clean_content($data);

        if (!$data) {
            throw new Exception('Could not connect');
        }

        $json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);

        $array = $json->decode($data);

        if (!$array) {
            throw new Exception('Could not parse the JSON');
        }

        if ($array['error']) {
            throw new Exception('Google reported an error: ' . $array['error']);
        }

        return (float) $array['rhs'];
    }
    public function currency_convert($Amount, $currencyfrom, $currencyto)
    {
        $url='http://finance.yahoo.com/currency-converter';
        $block_internet=$this->data->readconfig('block_internet');
        if ($block_internet>0) {
            return false;
        }
        $proxy=$this->data->readconfig('proxy');
        if ($proxy!='') {
            $aContext = array(
                'http' => array(
                    'proxy' => 'tcp://'.$proxy,
                    'request_fulluri' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            $buffer = file_get_contents($url, false, $cxContext);
        } else {
            $buffer = file_get_contents($url);
        }
        $buffer=$this->utils->clean_content($buffer);

        preg_match_all('/name=(\"|\')conversion-date(\"|\') value=(\"|\')(.*)(\"|\')>/i', $buffer, $match);
        $date=preg_replace('/name=(\"|\')conversion-date(\"|\') value=(\"|\')(.*)(\"|\')>/i', '$4', $match[0][0]);
        unset($buffer);
        unset($match);
        $url='http://finance.yahoo.com/currency/converter-results/'.$date.'/'.$Amount.'-'.strtolower($currencyfrom).'-to-'.strtolower($currencyto).'.html';

        $proxy=$this->data->readconfig('proxy');
        if ($proxy!='') {
            $aContext = array(
                'http' => array(
                    'proxy' => 'tcp://'.$proxy,
                    'request_fulluri' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            $buffer = file_get_contents($url, false, $cxContext);
        } else {
            $buffer = file_get_contents($url);
        }
        $buffer=$this->utils->clean_content($buffer);
        preg_match_all('/<span class=\"converted-result\">(.*)<\/span>/i', $buffer, $match);
        $match[0]=preg_replace('/<span class=\"converted-result\">(.*)<\/span>/i', '$1', $match[0]);
        //return $buffer;
        unset($buffer);
        return $match[0][0];
    }

    public function get_wiki_page($page = 'start', $dokuwiki_url = "")
    {
        if($dokuwiki_url=='')$dokuwiki_url=getenv('DOKUWIKI_URL');
        $url=$dokuwiki_url.'/doku.php?id='.$page;
        $content = file_get_contents($url);
        $rates=$content;
        $half=explode("<!-- wikipage start -->", $rates);
        $middle=explode("<!-- wikipage stop -->", $half[1]);
        $data=$middle[0];
        $data=str_ireplace("/lib/exe/", "$dokuwiki_url/lib/exe/", $data);
        $data=str_ireplace("/doku.php?id=", "?act=report&what=doku&dokupage=", $data);
        //$data='<link rel="stylesheet" type="text/css" href="'.$dokuwiki_url.'/lib/exe/css.php"/>'.$data;
        return $data;
    }

    public function getResultFromECB($base = 'EUR')
    {
        $source="https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL             => $source,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_TIMEOUT         => 2
        ));
        $result = curl_exec($curl);
        //echo $this->html->pre_display($result,"result");
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        // No HTTP error authorized
        if ($http_code != 200) {
            echo $this->html->message('HTTP status code ' . $http_code." <br>$curl ",__FUNCTION__,'alert-error');
            return null;
        }

        //echo $this->html->pre_display($result,"result");

        $xml = simplexml_load_string($result);
        //echo $this->html->pre_display($xml,"xml");
        $json = json_encode($xml);
        //echo $this->html->pre_display($json,"json");
        $array = json_decode($json,TRUE);
        //echo $this->html->pre_display($array,"array");
        $time=$array[Cube][Cube]['@attributes'][time];
        //echo $this->html->pre_display($time,"time");
        $date=$this->dates->F_date($time);
        //echo $this->html->pre_display($time,"time3");
        // Converting to an array
        $pattern = "{<Cube\s*currency='(\w*)'\s*rate='([\d\.]*)'/>}is";
        preg_match_all($pattern, $result, $xml_rates);
        array_shift($xml_rates);
        // Returning associative array (currencies -> rates)
        $result = array_combine($xml_rates[0], $xml_rates[1]);
        // Checking for Error
        if (empty($result)) {
            echo $this->html->message('empty result',__FUNCTION__,'alert-error');
            return null;
        }
        // Adding EUR = 1
        $result = array('EUR' => 1) + $result;
        if ($base!='EUR') {
            $rate=($result[$base]==0)?$rate=1:$rate=$result[$base];
            foreach ($result as $key => $value) {
                $result[$key]=round($value/$rate, 4);
            }
        }
        $res[date]=$date;
        $res[rates]=$result;
        return $res;
    }
    public function getResultFromYQL($yql_query, $env = '')
    {
        $yql_base_url = "http://query.yahooapis.com/v1/public/yql";
        $yql_base_url = "http://query.yahooapis.com/v1/public/yql";
        $yql_query_url = $yql_base_url . "?q=" . urlencode($yql_query);
        $yql_query_url .= "&format=json";
        if ($env != '') {
            $yql_query_url .= '&env=' . urlencode($env);
        }
        //echo $this->html->pre_display($yql_query_url,"yql_query_url");
        $session = curl_init($yql_query_url);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        //Uncomment if you are behind a proxy
        //curl_setopt($session, CURLOPT_PROXY, 'Your proxy url');
        //curl_setopt($session, CURLOPT_PROXYPORT, 'Your proxy port');
        //curl_setopt($session, CURLOPT_PROXYUSERPWD, 'Your proxy password');
        $json = curl_exec($session);
        curl_close($session);
        //return json_decode($json);
        return $json;
    }
    public function test()
    {
        return 'ok';
    }
}
