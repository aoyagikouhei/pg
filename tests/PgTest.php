<?php
class PgTest extends PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $db = new \Pg\Db(['server' => 'host=localhost dbname=test user=aoyagikouhei']);
        $this->assertEquals(0, $db->executeQuery('DROP TABLE IF EXISTS t_test'));
        $this->assertEquals(0, $db->executeQuery('CREATE TABLE t_test (id INT, name TEXT, flag BOOLEAN, ts TIMESTAMPTZ)'));
        $sql = <<<EOS
            CREATE OR REPLACE FUNCTION sp_test(
                p_ival INT DEFAULT NULL
                ,p_tval TEXT DEFAULT NULL
                ,p_bval BOOLEAN DEFAULT NULL
                ,p_tsval TIMESTAMPTZ DEFAULT NULL
            ) RETURNS TEXT AS \$FUNCTION\$
            DECLARE
            BEGIN
                RETURN COALESCE(p_ival, '') || COALESCE(p_tval, '') || COALESCE(p_bval, '') || COALESCE(p_tsval, '');
            END;
            \$FUNCTION\$ LANGUAGE plpgsql;
EOS;
        $this->assertEquals(0, $db->executeQuery($sql));
        
        $values = [
            ['id' => 1, 'name' => 'aaa', 'flag' => true, 'ts' => new DateTime('2010-01-01 00:00:00+09')]
            ,['id' => 2, 'name' => 'bbb', 'flag' => false, 'ts' => new DateTime('2010-01-02 00:00:00+09')]
        ];
        foreach ($values as $value) {
            $this->assertEquals(1, $db->executeQuery("INSERT INTO t_test (id, name, flag, ts) VALUES (:id, :name, :flag, :ts)", $value));
        }
        $this->checkList($values, $db->queryAll('select * from t_test order by id'));
        $this->checkList([$values[1]], $db->queryAll('select * from t_test where id = :id order by id', ['id' => 2]));

        //$db->queryAll('select * from t_test2');
        $db->close();
    }

    private function checkRow($expected, $real) {
        foreach ($real as $key => $value) {
                if ($value instanceof \DateTime) {
                    $this->assertEquals($expected[$key]->format('Y-m-d H:i:s'), $value->format('Y-m-d H:i:s'));
                } else {
                    $this->assertEquals($expected[$key], $value);
                }
            }
    }

    private function checkList($expected, $real) {
        $count = count($real);
        for ($i = 0; $i < $count; $i++) {
            $this->checkRow($expected[$i], $real[$i]);
        }
    }
}
