<?php
namespace Pg\Type;

class Json
{
    public static function o2r($src) 
    {
        if (is_null($src)) {
            return null;
        }
        return json_encode($src);
    }

    public static function r2o($src) 
    {
        if (is_null($src)) {
            return null;
        }
        return json_decode($src, true);
    }
}
