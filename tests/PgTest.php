<?php
class PgTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->db = new \Pg\Db(['server' => 'host=localhost dbname=test user=aoyagikouhei']);
    }

    public function testInit()
    {
        $this->db->query('DROP TABLE IF EXISTS t_test');
        $this->db->query('CREATE TABLE t_test (id INT, name TEXT, flag BOOLEAN, ts TIMESTAMPTZ, iary BIGINT[], tary TEXT[])');
        $this->db->query(<<<EOS
            CREATE OR REPLACE FUNCTION sp_test(
                p_ival INT DEFAULT NULL
                ,p_tval TEXT DEFAULT NULL
                ,p_bval BOOLEAN DEFAULT NULL
                ,p_tsval TIMESTAMPTZ DEFAULT NULL
            ) RETURNS TEXT AS \$FUNCTION\$
            DECLARE
            BEGIN
                RETURN 'x' || COALESCE(p_ival::TEXT, '') || COALESCE(p_tval::TEXT, '') || COALESCE(p_bval::TEXT, '') || COALESCE(p_tsval::TEXT, '');
            END;
            \$FUNCTION\$ LANGUAGE plpgsql;
EOS
        );
    }

    public function testParams()
    {
         $values = [
            ['id' => 1, 'name' => 'aaa', 'flag' => true, 'ts' => new DateTime('2010-01-01 00:00:00+09'), 'iary' => [1,2,3], 'tary' => ['a', 'b', 'c']]
            ,['id' => 2, 'name' => 'bbb', 'flag' => false, 'ts' => new DateTime('2010-01-02 00:00:00+09'), 'iary' => [4,5,6], 'tary' => ['e', 'f', 'g']]
        ];
        foreach ($values as $value) {
            $this->assertEquals(1, $this->db->queryAffectedCount("INSERT INTO t_test (id, name, flag, ts, iary, tary) VALUES (:id, :name, :flag, :ts, :iary, :tary)", $value));
        }
        $this->checkList($values, $this->db->queryAll('select id,name,flag,ts,iary,tary from t_test order by id'));
        $this->checkRow($values[1], $this->db->queryOne('select id,name,flag,ts,iary,tary from t_test where id = :id order by id', ['id' => 2]));
    }

    public function testTableNotExists()
    {
        try {
            $res = $this->db->queryAll('select * from t_test2');
            $this->fail('not raise');
        } catch (\Pg\Exception $e) {
            $this->assertEquals('42P01', $e->pgCode);
        }
    }

    public function testSp()
    {
        $this->assertEquals('x1', $this->db->queryValue("SELECT sp_test(:ival)", ['ival' => 1]));
        $this->assertEquals('x1a', $this->db->queryValue("SELECT sp_test(:ival, :tval)", ['ival' => 1, 'tval' => 'a']));
        $this->assertEquals('x', $this->db->queryValue("SELECT sp_test()", []));
        $this->assertEquals('x', $this->db->queryValue("SELECT sp_test(:params)", []));
        $this->assertEquals('x1', $this->db->queryValue("SELECT sp_test(:params)", ['ival' => 1]));
        $this->assertEquals('x1a', $this->db->queryValue("SELECT sp_test(:params)", ['ival' => 1, 'tval' => 'a']));
        $this->assertEquals('xatrue', $this->db->queryValue("SELECT sp_test(:params)", ['bval' => true, 'tval' => 'a', ]));
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
