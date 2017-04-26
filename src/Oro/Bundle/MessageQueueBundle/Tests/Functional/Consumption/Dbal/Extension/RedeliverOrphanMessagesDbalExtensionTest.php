<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption\Dbal\Extension;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RedeliverOrphanMessagesDbalExtension;
use Oro\Component\MessageQueue\Test\DbalSchemaExtensionTrait;
use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Psr\Log\NullLogger;

/**
 * @dbIsolationPerTest
 */
class RedeliverOrphanMessagesDbalExtensionTest extends WebTestCase
{
    use DbalSchemaExtensionTrait;

    protected function setUp()
    {
        $this->initClient();

        $this->ensureTableExists('message_queue');
    }

    protected function tearDown()
    {
        $this->dropTable('message_queue');
    }

    public function testShouldRedeliverOrphanMessages()
    {
        $connection = $this->createConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        // test
        $session = $connection->createSession();
        $context = new Context($session);
        $context->setLogger(new NullLogger());
        $consumer = new DbalMessageConsumer($session, new DbalDestination('default'));
        $context->setMessageConsumer($consumer);

        $dbal->insert('message_queue', [
            'consumer_id' => $consumer->getId(),
            'delivered_at' => strtotime('-1 year'),
            'redelivered' => false,
            'queue' => 'queue',
            'priority' => 1,
        ], ['redelivered' => Type::BOOLEAN]);
        $id = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id);

        $extension = new RedeliverOrphanMessagesDbalExtension();
        $extension->onBeforeReceive($context);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue WHERE id = ?', [$id])->fetchAll();

        $this->assertCount(1, $messages);
        $this->assertEquals($id, $messages[0]['id']);
        $this->assertNull($messages[0]['consumer_id']);
        $this->assertNull($messages[0]['delivered_at']);
        $this->assertTrue((bool) $messages[0]['redelivered']);
    }
}
