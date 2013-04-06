<?php
namespace Pg\Type;

class Int
{
    public static function o2r($src) 
    {
        return $src;
    }

    public static function r2o($src) 
    {
        return intval($src);
    }
}
