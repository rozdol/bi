<?php
namespace Rozdol\Utils;

class utf8
{

    private static $hInstance;
    public function __construct()
    {
# default font size used in plugin
        define("FONT_SIZE", 12);

# color gradient for utf8 line endings
//define("FONT_GRADIENT", serialize(array( "#6d80a0", "#99a1bd", "#bbbed9",  "#dde5f8")));
        define("FONT_GRADIENT", serialize(array( "#666666", "#999999", "#bbbbbb",  "#dddddd")));
    }
    public static function getInstance()
    {
        if (!self::$hInstance) {
            self::$hInstance = new utf8();
        }
        return self::$hInstance;
    }


    function utf8_strlen($str)
    {
        return preg_match_all('/./uSs', $str, $out);
    }

    function utf8_substr($str, $start, $length = null)
    {
        if (! isset($length)) {
            return preg_replace('/^.{'.$start.'}(.*)$/uSs', '$1', $str);
        } elseif ($length <= 0) {
            return "";
        }
        return preg_replace('/^.{'.$start.'}(.{'.$length.'}).*$/uSs', '$1', $str);
    }

    function utf8_getAllowedLength($str, $pixels, $bold = false)
    {
        $cyrUp = "[\x{0410}-\x{042F}]";
        $cyrLo = "[\x{0430}-\x{044F}]";
    
        $length = preg_match_all(
            "/([A-Z])|([a-z])|($cyrUp)|($cyrLo)|(.)/uSs",
            $str,
            $out
        );

        $curr = 0;
        for ($i = 0; $i < $length && $curr <= $pixels; $i++) {
            $val = 0;
            $val += $out[1][$i] == "" ? 0 : 11.5;     # mean capital latin
            $val += $out[2][$i] == "" ? 0 :  7.5;     # mean normal  latin
            $val += $out[3][$i] == "" ? 0 : 12.0;     # mean capital cyrilic
            $val += $out[4][$i] == "" ? 0 :  7.8;     # mean normal  cyrilic
            $val += $out[5][$i] == "" ? 0 :  7.0;     # mean others
            $val = $val * (FONT_SIZE - 4) / (12 - 4); # default font size is 12
            $curr += $bold ? ($val * 1.3) : $val;     # bold font difference
        }

        # TRUE means length is OK, string should not be cut
        return $i == $length ? true : $i;
    }

    function utf8_cutByPixel($str, $pixels, $bold = false)
    {
        $colors = unserialize(FONT_GRADIENT);
        $limit = $this->utf8_getAllowedLength($str, $pixels, $bold);
        if (true === $limit) {
            return $str;
        } elseif (0 === $limit) {
            return "";
        }

        $wishedStart = $limit - count($colors);
        for ($start = $wishedStart; $start < 0; $start++) {
        }
    
        $res = $this->utf8_substr($str, 0, $start);
        for ($i = $start; $i < $limit; $i++) {
            $char = $this->utf8_substr($str, $i, 1);
            $res .= '<font color="' . $colors[$i - $wishedStart] . '">';
            $res .= ($char == " " ? "&nbsp;" : $char) . "</font>";
        }

        return $res;
    }
    function cutByPixel($str, $pixels, $bold = false)
    {
        $colors = unserialize(FONT_GRADIENT);
        $limit = strlen($str);
        $pixels=3;
        $limit=5;
        $wishedStart = $limit - count($colors);
        for ($start = $wishedStart; $start < 0; $start++) {
        }
    
        $res = $this->utf8_substr($str, 0, $start);
        for ($i = $start; $i < $limit; $i++) {
            $char = $this->utf8_substr($str, $i, 1);
            $res .= '<font color="' . $colors[$i - $wishedStart] . '">';
            $res .= ($char == " " ? "&nbsp;" : $char) . "</font>";
        }

        return $res;
    }
}
