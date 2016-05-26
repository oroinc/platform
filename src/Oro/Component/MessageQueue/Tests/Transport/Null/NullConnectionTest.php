<?php
namespace Oro\Component\MessageQueue\Tests\Transport\Null;

use Oro\Component\MessageQueue\Transport\Connection;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\Testing\ClassExtensionTrait;

class NullConnectionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionInterface()
    {
        $this->assertClassImplements(Connection::class, NullConnection::class);
    }
    
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new NullConnection();
    }
    
    public function testShouldCreateNullSession()
    {
        $connection = new NullConnection();

        $this->assertInstanceOf(NullSession::class, $connection->createSession());
    }
}
