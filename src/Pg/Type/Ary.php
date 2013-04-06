<?php
namespace Pg\Type;

class Ary
{
    public static function o2r($src) 
    {
        $res = [];
        for($i=0; $i<count($src); $i++){
            $it = $src[$i];
            if (false === $it) {
                array_push($res, '"0"');
            } elseif (is_null($it) || strlen($it) == 0) {
                array_push($res, "null");
            } else {
                $it = preg_replace("|\\\\|", "\\\\\\\\", $it);
                array_push($res, '"' . preg_replace('|"|', "\\\"", $it) . '"');
            }
        }
        return "{". implode(",", $res) ."}";
    }

    public static function r2o($src) 
    {
        if (is_null($src)) {
            return null;
        }
  
        if (empty($src) || "{}" == $src ) {
            return [];
        }
        $res = [];
        $count = strlen($src) - 2;
        $item = '';
        $quote = false;
        $bs = false;
      
        for ($i=1; $i<=$count; $i++) {
            if ("\\" == $src[$i]) {
                if ($bs) {
                    $item .= "\\";
                    $bs = false;
                } else {
                    $bs = true;
                }
            } else if (',' == $src[$i]) {
                if ($quote) {
                    $item .= ',';
                } else {
                    $res[] = $item;
                    $item = '';
                }
            } else if('"' == $src[$i]) {
                if ($bs) {
                    $item .= '"';
                } else if($quote) {
                    $quote = false;
                } else {
                    $quote = true;
                }
            } else {
                if ($bs) {
                    $item .= "\\";
                }
                $item .= $src[$i];
            }
            if ("\\" != $src[$i]) {
                $bs = false;
            }
        }
        if ('' != $item) {
            $res[] = $item;
        }
        return $res;
    }
}
