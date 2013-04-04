<?php
class PgTest extends PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $handler = new \Pg([]);
    }
}
