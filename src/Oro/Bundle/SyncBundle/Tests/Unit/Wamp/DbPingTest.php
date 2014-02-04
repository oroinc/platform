<?php
namespace Oro\Bundle\SyncBundle\Tests\Unit\Wamp;

use Oro\Bundle\SyncBundle\Wamp\DbPing;

class DbPingTest extends \PHPUnit_Framework_TestCase
{
    public function testTick()
    {
        $pdoMock = new PDO(1, 2, 3);
        $ping = new DbPing($pdoMock);
        $ping->tick();
        $this->assertEquals('SELECT 1', $pdoMock->getQuery());
    }
}
