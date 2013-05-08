<?php
namespace Pg;

class TypeConverter
{
    public function convert($value, $type) 
    {
        if (is_null($value)) {
            return null;
        }
        switch ($type) {
            case 'int2' : 
            case 'int4' : 
            case 'int8' : 
                return $this->convertInt($value);
                break;
            case 'bool' :
                return $this->convertBoolean($value);
                break;
            case 'timestamptz' :
                return $this->convertTimestamp($value);
                break;
            case '_int2' :
            case '_int4' :
            case '_int8' :
            case '_text' :
            case '_bool' :
            case '_timestamptz' :
                return $this->convertAry($value, $type);
                break;
            default :
                return $value;
        }
    }

    public function convertInt($value) {
        return \Pg\Type\Int::r2o($value);
    }

    public function convertBoolean($value) {
        return \Pg\Type\Boolean::r2o($value);
    }

    public function convertTimestamp($value) {
        return \Pg\Type\Timestamp::r2o($value);
    }

    public function convertAry($value, $type) {
        return \Pg\Type\Ary::r2o($value);
    }
}
