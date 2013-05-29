<?php
namespace Pg;

class Db
{
    private $dbconn;
    private $typeConverter;

    function __construct($params)
    {
        $this->dbconn = pg_pconnect($params['server']);
        if (false === $this->dbconn) {
            throw new \Pg\Exception(pg_last_error());
        }
        if (isset($params['type_converter'])) {
            $this->typeConverter = $params['type_converter'];
        } else {
            $this->typeConverter = new \Pg\TypeConverter();
        }
        pg_set_error_verbosity($this->dbconn, PGSQL_ERRORS_VERBOSE);
    }

    public function getTypeConverter()
    {
        return $this->typeConverter;
    }

    public function setTypeConverter($typeConverter)
    {
        return $this->typeConverter = $typeConverter;
    }

    public function query($sql, array $params=[]) 
    {
        if (1 === preg_match('/:params[^a-zA-Z0-9_]/', $sql)) {
            return $this->convertParamsQuery($sql, $params);
        } elseif (empty($params)) {
            return $this->rawQuery($sql);
        }
        $newParams = [];
        $callback = function($matches) use ($params, &$newParams) {
            $newParams[] = $this->typeConverter->o2r(
                $matches[1]
                ,$params[$matches[1]]
            );
            return '$' . count($newParams);
        };
        $newSql = preg_replace_callback('/:([a-zA-Z0-9_]+)/', $callback, $sql);
        return $this->rawQuery($newSql, $newParams);
    }

    public function rawQuery($sql, array $params=[]) 
    {
        if (empty($params)) {
            pg_send_query($this->dbconn, $sql);
        } else {
            pg_send_query_params($this->dbconn, $sql, $params);
        }
        $result = pg_get_result($this->dbconn);
        $err = pg_result_error($result);
        if ($err) {
            throw new \Pg\Exception($err, 0, null, pg_result_error_field($result, PGSQL_DIAG_SQLSTATE));
        }
        return new \Pg\Statement($result, $this->typeConverter);
    }

    public function queryValue($sql, array $params=[])
    {
        return $this->query($sql, $params)->value();
    }

    public function queryOne($sql, array $params=[])
    {
        return $this->query($sql, $params)->one();
    }

    public function queryAll($sql, array $params=[])
    {
        return $this->query($sql, $params)->all();
    }

    public function queryAffectedCount($sql, array $params=[])
    {
        return $this->query($sql, $params)->getAffectedCount();
    }

    private function convertParamsQuery($sql, $params) {
        $strParams = '';
        $newParams = [];
        $index = 1;
        foreach ($params as $key => $value) {
            if (1 !== $index) {
                $strParams .= ', ';
            }
            $strParams .= 'p_' . $key . ' := ' . '\$' . $index;
            $index += 1;
            $newParams[] = $this->typeConverter->o2r($key, $value);
        }
        return $this->rawQuery(preg_replace('/:params/', $strParams, $sql), $newParams);
    }
}
