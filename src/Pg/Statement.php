<?php
namespace Pg;

class Statement
{
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        if (false === $this->result) {
            return;
        }
        pg_free_result($this->result);
        $this->result = false;
    }

    private function getTypeMap() 
    {
        $count = $this->getColumnCount();
        $typeMap = [];
        for ($i = 0; $i < $count; $i++) {
            $typeMap[$this->getColumnName($i)] = $this->getType($i);
        }
        return $typeMap;
    }

    private function makeRow($row, $typeMap)
    {
        $result = [];
        foreach ($row as $key => $value) {
            $result[$key] = $this->convertTypeValue($value, $typeMap[$key]);
        }
        return $result;
    }

    private function convertTypeValue($value, $type) 
    {
        if (is_null($value)) {
            return null;
        }
        switch ($type) {
            case 'int2' : 
            case 'int4' : 
            case 'int8' : 
                return \Pg\Type\Int::r2o($value);
                break;
            case 'bool' :
                return \Pg\Type\Boolean::r2o($value);
                break;
            case 'timestamptz' :
                return \Pg\Type\Timestamp::r2o($value);
                break;
            case '_int2' :
            case '_int4' :
            case '_int8' :
            case '_text' :
            case '_bool' :
            case '_timestamptz' :
                return \Pg\Type\Ary::r2o($value);
                break;
            default :
                return $value;
        }
    }

    public function getColumnName($index)
    {
        return pg_field_name($this->result, $index);
    }

    public function getColumnCount()
    {
        return pg_num_fields($this->result);
    }

    public function getType($index)
    {
        return pg_field_type($this->result, $index);
    }

    public function isEmpty()
    {
        return 0 === pg_num_rows($this->result);
    }

    public function value()
    {
        if ($this->isEmpty()) {
            return null;
        }
        $value = pg_fetch_result($this->result, 0, 0);
        return $this->convertTypeValue($value, $this->getType(0));
    }

    public function one()
    {
        if ($this->isEmpty()) {
            return null;
        }
        $typeMap = $this->getTypeMap();   
        $row = $this->makeRow(pg_fetch_assoc($this->result), $typeMap);
        return $row;
    }

    public function all()
    {
        $list = pg_fetch_all($this->result);
        if (false === $list || empty($list)) {
            return [];
        }
        $typeMap = $this->getTypeMap();
        $func = function ($row) use ($typeMap) {
            return $this->makeRow($row, $typeMap);
        };
        return array_map($func, $list);
    }

    public function getAffectedCount() 
    {
        return pg_affected_rows($this->result);
    }
}
