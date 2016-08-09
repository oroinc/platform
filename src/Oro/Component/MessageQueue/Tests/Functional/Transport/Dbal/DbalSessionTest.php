<?php
namespace Oro\Component\MessageQueue\Tests\Functional\Transport\Dbal;

use Doctrine\DBAL\Exception\DriverException;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;

class DbalSessionTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();

        $this->startTransaction();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();
    }

    public function testShouldCreateMessageQueueTableIfNotExistOnDeclareQueue()
    {
        $connection = $this->createConnection();
        $dbal = $connection->getDBALConnection();

        // guard
        try {
            $dbal->getSchemaManager()->dropTable('message_queue');
        } catch (DriverException $e) {
        }
        $this->assertNotContains('message_queue', $dbal->getSchemaManager()->listTableNames());

        // test
        $session = new DbalSession($connection);
        $session->declareQueue($session->createQueue('name'));

        $this->assertContains('message_queue', $dbal->getSchemaManager()->listTableNames());
    }

    /**
     * @return DbalConnection
     */
    private function createConnection()
    {
        $dbal = $this->getContainer()->get('doctrine.dbal.default_connection');

        return new DbalConnection($dbal, 'message_queue');
    }
}
