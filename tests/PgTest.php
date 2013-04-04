<?php
class PgTest extends PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $db = new \Pg\Db(['server' => 'host=localhost dbname=test user=aoyagikouhei']);
        $this->assertEquals(0, $db->executeQuery('DROP TABLE IF EXISTS t_test'));
        $this->assertEquals(0, $db->executeQuery('CREATE TABLE t_test (id INT, name TEXT, flag BOOLEAN)'));
        $values = [
            ['id' => 1, 'name' => 'aaa', 'flag' => true]
            ,['id' => 2, 'name' => 'bbb', 'flag' => false]
        ];
        foreach ($values as $value) {
            $this->assertEquals(1, $db->executeQuery("INSERT INTO t_test (id, name, flag) VALUES (:id, :name, :flag)", $value));
        }
        $this->assertSame($values, $db->queryAll('select * from t_test order by id'));
        $db->close();
    }
}
