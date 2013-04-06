<?php
namespace Pg\Type;

class Timestamp
{
    public static function o2r($src) 
    {
        return $src->format('Y-m-d H:i:s O');
    }

    public static function r2o($src) 
    {
        return new \DateTime($src);
    }
}
