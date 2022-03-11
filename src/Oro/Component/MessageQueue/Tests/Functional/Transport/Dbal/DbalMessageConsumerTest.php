<?php

namespace Oro\Component\MessageQueue\Tests\Functional\Transport\Dbal;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Test\DbalSchemaExtensionTrait;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;
use Oro\Component\MessageQueue\Transport\Queue;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * @dbIsolationPerTest
 */
class DbalMessageConsumerTest extends WebTestCase
{
    use DbalSchemaExtensionTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->ensureTableExists('message_queue');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->dropTable('message_queue');
    }

    public function testShouldRemoveRecordIfMessageIsAcknowledged(): void
    {
        $connection = $this->createConnection('message_queue');
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
        $consumer = new DbalMessageConsumer($session, new Queue('default'));

        $consumer->acknowledge($message);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue WHERE id = ?', [$id])->fetchAllAssociative();

        $this->assertEmpty($messages);
    }

    public function testShouldRemoveRecordIfMessageIsRejected(): void
    {
        $connection = $this->createConnection('message_queue');
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
        $consumer = new DbalMessageConsumer($session, new Queue('default'));

        $consumer->reject($message);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue WHERE id = ?', [$id])->fetchAllAssociative();

        $this->assertEmpty($messages);
    }

    public function testShouldUpdateRecordIfMessageIsRequeued(): void
    {
        $connection = $this->createConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $dbal->insert('message_queue', [
            'queue' => 'queue',
            'priority' => 1,
            'consumer_id' => 123,
            'body' => 'sample body',
        ]);
        $id = (int) $dbal->lastInsertId('message_queue_id_seq');

        $this->assertGreaterThan(0, $id);
        $messages = $dbal->executeQuery('SELECT * FROM message_queue')->fetchAllAssociative();
        $this->assertCount(1, $messages);
        $this->assertEquals($id, $messages[0]['id']);
        $this->assertEquals(123, $messages[0]['consumer_id']);
        $this->assertEquals('sample body', $messages[0]['body']);
        $this->assertNull($messages[0]['redelivered']);

        $message = new DbalMessage();
        $message->setId($id);
        $message->setBody('updated body');

        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new Queue('default'));

        $consumer->reject($message, true);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue')->fetchAllAssociative();

        $this->assertCount(1, $messages);
        $this->assertEquals($id, $messages[0]['id'], 'ID was not expected to be changed.');
        $this->assertEquals('sample body', $messages[0]['body'], 'Requeued message body must not to be changed.');
        $this->assertNull($messages[0]['consumer_id'], 'Requeued message must not be assigned to a consumer.');
        $this->assertTrue((bool) $messages[0]['redelivered'], 'Requeued message must have redelivered flag.');
    }

    public function testShouldReceiveMessage(): void
    {
        $connection = $this->createConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $messageBody = 'message';
        $dbal->insert('message_queue', [
            'priority' => 1,
            'body' => JSON::encode($messageBody),
            'queue' => 'default',
        ]);
        $id = (int) $dbal->lastInsertId('message_queue_id_seq');

        //guard
        $this->assertGreaterThan(0, $id);

        // test
        $session = new DbalSession($connection);
        $consumer = new DbalMessageConsumer($session, new Queue('default'));

        $message = $consumer->receive();

        $this->assertInstanceOf(DbalMessage::class, $message);
        $this->assertEquals($id, $message->getId());
        $this->assertEquals($messageBody, $message->getBody());
    }

    public function testShouldReceiveMessageWithHighestPriorityFirst(): void
    {
        $connection = $this->createConnection('message_queue');
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
        $consumer = new DbalMessageConsumer($session, new Queue('default'));

        $message = $consumer->receive();

        $this->assertEquals($id2, $message->getId());
        $this->assertEquals(10, $message->getPriority());

        $consumer->acknowledge($message);
        $message = $consumer->receive();

        $this->assertEquals($id1, $message->getId());
        $this->assertEquals(5, $message->getPriority());
    }

    public function testShouldReceiveMessagesWithSamePriorityInIncomeOrder(): void
    {
        $connection = $this->createConnection('message_queue');
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
        $consumer = new DbalMessageConsumer($session, new Queue('default'));

        $message = $consumer->receive();

        $this->assertEquals($id1, $message->getId());
        $this->assertEquals(5, $message->getPriority());

        $consumer->acknowledge($message);
        $message = $consumer->receive();

        $this->assertEquals($id2, $message->getId());
        $this->assertEquals(5, $message->getPriority());
    }

    public function testShouldReceiveDelayedMessageIfDelayedUntilTimeInThePast(): void
    {
        $connection = $this->createConnection('message_queue');
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
        $consumer = new DbalMessageConsumer($session, new Queue('default'));

        $message = $consumer->receive();

        $this->assertEquals($id, $message->getId());
    }
}
