<?php
namespace Pg;

class Statement
{
    private $result;
    private $typeConverter;

    public function __construct($result, $typeConverter)
    {
        $this->result = $result;
        $this->typeConverter = $typeConverter;
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
            $result[$key] = $this->executeConvertTypeValue($key, $value, $typeMap[$key]);
        }
        return $result;
    }

    private function executeConvertTypeValue($name, $value, $type) {
        return $this->typeConverter->r2o($name, $value, $type);
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
        return $this->executeConvertTypeValue($this->getColumnName(0), $value, $this->getType(0));
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
