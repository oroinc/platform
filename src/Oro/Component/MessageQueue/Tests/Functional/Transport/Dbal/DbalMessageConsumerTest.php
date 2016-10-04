<?php
namespace Oro\Component\MessageQueue\Tests\Functional\Transport\Dbal;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Test\DbalSchemaExtensionTrait;
use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;

class DbalMessageConsumerTest extends WebTestCase
{
    use DbalSchemaExtensionTrait;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();

        $this->messageQueueEnsureTableExists('message_queue');

        $this->startTransaction();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();

        $this->messageQueueDropTable('message_queue');
    }

    public function testShouldRemoveRecordIfMessageIsAcknowledged()
    {
        $connection = $this->messageQueueCreateConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $dbal->insert('message_queue', [
            'queue' => 'queue',
            'priority' => 1,
        ]);
        $id = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id);

        // test
        $message = new DbalMessage();
        $message->setId($id);

        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new DbalDestination('default'));

        $consumer->acknowledge($message);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue WHERE id = ?', [$id])->fetchAll();

        $this->assertEmpty($messages);
    }

    public function testShouldRemoveRecordIfMessageIsRejected()
    {
        $connection = $this->messageQueueCreateConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $dbal->insert('message_queue', [
            'queue' => 'queue',
            'priority' => 1,
        ]);
        $id = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id);

        // test
        $message = new DbalMessage();
        $message->setId($id);

        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new DbalDestination('default'));

        $consumer->reject($message);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue WHERE id = ?', [$id])->fetchAll();

        $this->assertEmpty($messages);
    }

    public function testShouldRemoveRecordAndCreateNewOneIfMessageIsRequeued()
    {
        $connection = $this->messageQueueCreateConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $dbal->insert('message_queue', [
            'queue' => 'queue',
            'priority' => 1,
            'consumer_id' => 123,
        ]);
        $id = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id);
        $messages = $dbal->executeQuery('SELECT * FROM message_queue')->fetchAll();
        $this->assertCount(1, $messages);
        $this->assertEquals($id, $messages[0]['id']);
        $this->assertEquals(123, $messages[0]['consumer_id']);
        $this->assertNull($messages[0]['redelivered']);

        // test
        $message = new DbalMessage();
        $message->setId($id);

        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new DbalDestination('default'));

        $consumer->reject($message, true);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue')->fetchAll();

        $this->assertCount(1, $messages);
        $this->assertNotEquals($id, $messages[0]['id']);
        $this->assertNull($messages[0]['consumer_id']);
        $this->assertTrue((bool) $messages[0]['redelivered']);
    }

    public function testShouldReceiveMessage()
    {
        $connection = $this->messageQueueCreateConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $dbal->insert('message_queue', [
            'priority' => 1,
            'body' => 'message',
            'queue' => 'default',
        ]);
        $id = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id);

        // test
        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new DbalDestination('default'));

        $message = $consumer->receive();

        $this->assertInstanceOf(DbalMessage::class, $message);
        $this->assertEquals($id, $message->getId());
        $this->assertEquals('message', $message->getBody());
    }

    public function testShouldReceiveMessageWithHighestPriorityFirst()
    {
        $connection = $this->messageQueueCreateConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $dbal->insert('message_queue', [
            'priority' => 5,
            'queue' => 'default',
        ]);
        $id1 = (int) $dbal->lastInsertId('message_queue_id_seq');

        $dbal->insert('message_queue', [
            'priority' => 10,
            'queue' => 'default',
        ]);
        $id2 = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id1);
        $this->assertGreaterThan(0, $id2);

        // test
        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new DbalDestination('default'));

        $message = $consumer->receive();

        $this->assertEquals($id2, $message->getId());
        $this->assertEquals(10, $message->getPriority());

        $consumer->acknowledge($message);
        $message = $consumer->receive();

        $this->assertEquals($id1, $message->getId());
        $this->assertEquals(5, $message->getPriority());
    }

    public function testShouldReceiveMessagesWithSamePriorityInIncomeOrder()
    {
        $connection = $this->messageQueueCreateConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $dbal->insert('message_queue', [
            'priority' => 5,
            'queue' => 'default',
        ]);
        $id1 = (int) $dbal->lastInsertId('message_queue_id_seq');

        $dbal->insert('message_queue', [
            'priority' => 5,
            'queue' => 'default',
        ]);
        $id2 = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id1);
        $this->assertGreaterThan(0, $id2);

        // test
        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new DbalDestination('default'));

        $message = $consumer->receive();

        $this->assertEquals($id1, $message->getId());
        $this->assertEquals(5, $message->getPriority());

        $consumer->acknowledge($message);
        $message = $consumer->receive();

        $this->assertEquals($id2, $message->getId());
        $this->assertEquals(5, $message->getPriority());
    }

    public function testShouldNotReceiveDelayedMessageIfDelayedUntilTimeInTheFuture()
    {
        $connection = $this->messageQueueCreateConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $dbal->insert('message_queue', [
            'delayed_until' => time() + 9999,
            'queue' => 'default',
            'priority' => 1,
        ]);
        $id = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id);

        // test
        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new DbalDestination('default'));

        $message = $consumer->receiveNoWait();

        $this->assertEmpty($message);
    }

    public function testShouldReceiveDelayedMessageIfDelayedUntilTimeInThePast()
    {
        $connection = $this->messageQueueCreateConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $dbal->insert('message_queue', [
            'delayed_until' => time() - 9999,
            'queue' => 'default',
            'priority' => 1,
        ]);
        $id = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id);

        // test
        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new DbalDestination('default'));

        $message = $consumer->receive();

        $this->assertEquals($id, $message->getId());
    }
}
