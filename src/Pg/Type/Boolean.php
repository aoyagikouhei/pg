<?php
namespace Pg\Type;

class Boolean
{
    public static function o2r($src) 
    {
        return $src ? 't' : 'f';
    }

    public static function r2o($src) 
    {
        return 't' === $src || 'true' === $src;
    }
}
