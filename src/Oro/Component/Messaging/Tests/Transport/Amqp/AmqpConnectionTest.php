<?php
namespace Oro\Component\Messaging\Tests\Transport\Amqp;

use Oro\Component\Testing\ClassExtensionTrait;

class AmqpConnectionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\Connection',
            'Oro\Component\Messaging\Transport\Amqp\AmqpConnection'
        );
    }
}
