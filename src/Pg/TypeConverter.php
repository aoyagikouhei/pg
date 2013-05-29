<?php
namespace Pg;

class TypeConverter
{
    public function o2r($name, $value)
    {
        if (is_bool($value)) {
            return $this->o2rBool($value);
        } else if ($value instanceof \DateTime) {
            return $this->o2rTs($value);
        } else if (is_array($value)) {
            return $this->o2rAry($value);
        } else {
            return $value;
        }
    }

    public function r2o($name, $value, $type) 
    {
        if (is_null($value)) {
            return null;
        }
        switch ($type) {
            case 'int2' : 
            case 'int4' : 
            case 'int8' : 
                return $this->r2oInt($value);
                break;
            case 'bool' :
                return $this->r2oBool($value);
                break;
            case 'timestamptz' :
                return $this->r2oTs($value);
                break;
            case 'json' :
                return $this->r2oJson($value);
                break;
            case '_int2' :
            case '_int4' :
            case '_int8' :
            case '_text' :
            case '_bool' :
            case '_timestamptz' :
                return $this->r2oAry($value, $type);
                break;
            default :
                return $value;
        }
    }

    public function o2rInt($value)
    {
        return \Pg\Type\Int::o2r($value);
    }

    public function r2oInt($value)
    {
        return \Pg\Type\Int::r2o($value);
    }

    public function o2rBool($value)
    {
        return \Pg\Type\Boolean::o2r($value);
    }

    public function r2oBool($value)
    {
        return \Pg\Type\Boolean::r2o($value);
    }

    public function o2rTs($value)
    {
        return \Pg\Type\Timestamp::o2r($value);
    }

    public function r2oTs($value)
    {
        return \Pg\Type\Timestamp::r2o($value);
    }

    public function o2rAry($value)
    {
        return \Pg\Type\Ary::o2r($value);
    }

    public function r2oAry($value, $type)
    {
        return \Pg\Type\Ary::r2o($value);
    }

    public function o2rJson($value)
    {
        return \Pg\Type\Json::o2r($value);
    }

    public function r2oJson($value)
    {
        return \Pg\Type\Json::r2o($value);
    }
}
