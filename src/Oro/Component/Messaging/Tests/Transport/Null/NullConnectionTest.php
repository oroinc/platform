<?php
namespace Oro\Component\Messaging\Tests\Transport\Null;

use Oro\Component\Messaging\Transport\Null\NullConnection;
use Oro\Component\Messaging\Transport\Null\NullSession;
use Oro\Component\Testing\ClassExtensionTrait;

class NullConnectionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\Connection',
            'Oro\Component\Messaging\Transport\Null\NullConnection'
        );
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
