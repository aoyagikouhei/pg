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
        pg_set_error_verbosity($this->dbconn, PGSQL_ERRORS_VERBOSE);
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
            case 'timestamptz' :
                return new \DateTime($value);
                break;
            default :
                return $value;
        }
    }

    private function exec($sql, $params)
    {
        if (empty($params)) {
            pg_send_query($this->dbconn, $sql);
        } else {
            $newParams = [];
            $callback = function($matches) use ($params, &$newParams) {
                $value = $params[$matches[1]];
                if (is_bool($value)) {
                    $value = $value ? 't' : 'f';
                } else if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s O');
                }
                $newParams[] = $value;
                return '$' . count($newParams);
            };
            $newSql = preg_replace_callback('/:([a-zA-Z0-9_]+)/', $callback, $sql);
            pg_send_query_params($this->dbconn, $newSql, $newParams);
        }
        $result = pg_get_result($this->dbconn);
        $err = pg_result_error($result);
        if ($err) {
            throw new \Pg\Exception($err, 0, null, pg_result_error_field($result, PGSQL_DIAG_SQLSTATE));
        }
        return $result;
    }

    public function queryAll($sql, array $params = []) 
    {
        $result = $this->exec($sql, $params);
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
        return pg_affected_rows($result);
    }

    public function close()
    {
        if (false !== $this->dbconn) {
            pg_close($this->dbconn);
        }
    }
}
