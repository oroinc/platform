<?php
namespace Oro\Bundle\MessagingBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Messaging\Transport\Connection;

class ConnectionTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], [], true);
    }
    
    public function testCouldBeGetFromContainerAsService()
    {
        $connection = $this->getContainer()->get('oro_messaging.transport.connection');
        
        $this->assertInstanceOf(Connection::class, $connection);
    }
}
