<?php
namespace Pg;

class Db
{
    private $dbconn;

    public function __construct($params)
    {
        $this->dbconn = pg_connect($params['server']);
        if (false === $this->dbconn) {
            throw new \Pg\Exception(pg_last_error());
        }
    }

    private function getTypeMap($result) 
    {
        $count = pg_num_fields($result);
        $typeMap = [];
        for ($i = 0; $i < $count; $i++) {
            $typeMap[pg_field_name($result, $i)] = pg_field_type($result, $i);
        }
        return $typeMap;
    }

    private function makeRow($row, $typeMap)
    {
        $result = [];
        foreach ($row as $key => $value) {
            $result[$key] = $this->getValue($value, $typeMap[$key]);
        }
        return $result;
    }

    private function getValue($value, $type) 
    {
        switch ($type) {
            case 'int4' : 
                return intval($value);
                break;
            case 'bool' :
                return 't' === $value;
                break;
            default :
                return $value;
        }
    }

    private function exec($sql, $params)
    {
        if (empty($params)) {
            return pg_query($this->dbconn, $sql);
        }
        $newParams = [];
        $callback = function($matches) use ($params, &$newParams) {
            $value = $params[$matches[1]];
            if (is_bool($value)) {
                $value = $value ? 't' : 'f';
            }
            $newParams[] = $value;
            return '$' . count($newParams);
        };
        $newSql = preg_replace_callback('/:([^, )]+)/', $callback, $sql);
        return pg_query_params($this->dbconn, $newSql, $newParams);
    }

    public function queryAll($sql, array $params = []) 
    {
        $result = $this->exec($sql, $params);
        if (false === $result) {
            throw new \Pg\Exception(pg_last_error());
        }
        $list = [];
        $typeMap = $this->getTypeMap($result);
        while ($row = pg_fetch_assoc($result)) {
            $list[] = $this->makeRow($row, $typeMap);
        }
        pg_free_result($result);
        return $list;
    }

    public function executeQuery($sql, array $params = [])
    {
        $result = $this->exec($sql, $params);
        if (false === $result) {
            throw new \Pg\Exception(pg_last_error());
        }
        return pg_affected_rows($result);
    }

    public function close()
    {
        if (false !== $this->dbconn) {
            pg_close($this->dbconn);
        }
    }
}
