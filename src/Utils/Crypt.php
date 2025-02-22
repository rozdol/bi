<?php
namespace Rozdol\Utils;

use Rozdol\utils\Utils;
use Rozdol\Html\Html;

class Crypt
{

    private static $hInstance;

    public function __construct()
    {
        $this->funcs = Utils::getInstance();
        $this->html = Html::getInstance();
        define("PBKDF2_HASH_ALGORITHM", "sha256");
        define("PBKDF2_ITERATIONS", 1000);
        define("PBKDF2_SALT_BYTES", 24);
        define("PBKDF2_HASH_BYTES", 24);

        define("HASH_SECTIONS", 4);
        define("HASH_ALGORITHM_INDEX", 0);
        define("HASH_ITERATION_INDEX", 1);
        define("HASH_SALT_INDEX", 2);
        define("HASH_PBKDF2_INDEX", 3);
    }
    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new Crypt();
        }
        return self::$hInstance;
    }
    /*
    * Password hashing with PBKDF2.
    * Author: havoc AT defuse.ca
    * www: https://defuse.ca/php-pbkdf2.htm
    */

    // These constants may be changed without breaking existing hashes.


    function create_hash($password)
    {
        
        // format: algorithm:iterations:salt:hash
        //$salt = base64_encode(mcrypt_create_iv(PBKDF2_SALT_BYTES, MCRYPT_DEV_URANDOM));

        $encrypt_method = "AES-256-CBC";
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        $salt = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
        return PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" .  $salt . ":" .
        base64_encode($this->pbkdf2(
            PBKDF2_HASH_ALGORITHM,
            $password,
            $salt,
            PBKDF2_ITERATIONS,
            PBKDF2_HASH_BYTES,
            true
        ));
    }

    function validate_password($password, $good_hash)
    {
        $params = explode(":", $good_hash);
        if (count($params) < HASH_SECTIONS) {
            return false;
        }
        $pbkdf2 = base64_decode($params[HASH_PBKDF2_INDEX]);
        return $this->slow_equals(
            $pbkdf2,
            $this->pbkdf2(
                $params[HASH_ALGORITHM_INDEX],
                $password,
                $params[HASH_SALT_INDEX],
                (int)$params[HASH_ITERATION_INDEX],
                strlen($pbkdf2),
                true
            )
        );
    }

    // Compares two strings $a and $b in length-constant time.
    function slow_equals($a, $b)
    {
        $diff = strlen($a) ^ strlen($b);
        for ($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $diff === 0;
    }

    /*
    * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
    * $algorithm - The hash algorithm to use. Recommended: SHA256
    * $password - The password.
    * $salt - A salt that is unique to the password.
    * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
    * $key_length - The length of the derived key in bytes.
    * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
    * Returns: A $key_length-byte key derived from the password and salt.
    *
    * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
    *
    * This implementation of PBKDF2 was originally created by https://defuse.ca
    * With improvements by http://www.variations-of-shadow.com
    */
    function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
    {
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_algos(), true)) {
            die('PBKDF2 ERROR: Invalid hash algorithm.');
        }
        if ($count <= 0 || $key_length <= 0) {
            die('PBKDF2 ERROR: Invalid parameters.');
        }

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if ($raw_output) {
            return substr($output, 0, $key_length);
        } else {
            return bin2hex(substr($output, 0, $key_length));
        }
    }
    function csrf_token()
    {
        $secret= '=djk$fgj&mFD$5';
        $token= substr(dechex(mt_rand()), 1, 4);
        $hash= substr(sha1($secret.'-'.$token), 3, 8);
        $signed= $token.'-'.$hash;
        return $signed;
    }
    function csrf_chk()
    {
        $unsafe_acts=array('save','delete','append');
        if (is_array($GLOBALS['unsafe_acts'])) {
            $unsafe_acts=array_merge($unsafe_acts, $GLOBALS['unsafe_acts']);
        }
        //echo $this->html->pre_display($unsafe_acts,"result");
        $ignore_obj=array('shoppingcart','webcam','dropzone');
        $csrf=$this->html->readRQc('csrf');
        $act=$this->html->readRQ('act');
        $what=$this->html->readRQ('what');
        if (($act=='')||(in_array($what, $ignore_obj))) {
            return true;
        } else {
            if ((in_array($act, $unsafe_acts))) {
                if ($csrf==$GLOBALS['csrf']) {
                    return true;
                } else {
                    //$this->data->DB_log("HACK on CSRF. ($act)");
                    echo "No permission for this action [$act].<br>csrf token not valid.";
                    exit;
                    return false;
                }
            } else {
                return true;
            }
        }
    }
}
