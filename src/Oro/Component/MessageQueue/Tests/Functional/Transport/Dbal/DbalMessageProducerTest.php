<?php
namespace Oro\Component\MessageQueue\Tests\Functional\Transport\Dbal;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Test\DbalSchemaExtensionTrait;
use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageProducer;

class DbalMessageProducerTest extends WebTestCase
{
    use DbalSchemaExtensionTrait;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();

        $this->ensureTableExists('message_queue');

        $this->startTransaction();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();

        $this->dropTable('message_queue');
    }

    public function testShouldCreateMessageInDb()
    {
        $connection = $this->createConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $producer = new DbalMessageProducer($connection);

        // guard
        $messages = $dbal->executeQuery('SELECT * FROM message_queue')->fetchAll();
        $this->assertEmpty($messages);

        $message = new DbalMessage();
        $message->setBody('body');
        $message->setHeaders([
            'hkey' => 'hvalue',
        ]);
        $message->setProperties([
            'pkey' => 'pvalue',
        ]);

        // test
        $producer->send(new DbalDestination('default'), $message);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue')->fetchAll();

        $this->assertCount(1, $messages);
        $this->assertNotEmpty($messages[0]['id']);
        $this->assertEquals('body', $messages[0]['body']);
        $this->assertEquals('{"hkey":"hvalue"}', $messages[0]['headers']);
        $this->assertEquals('{"pkey":"pvalue"}', $messages[0]['properties']);
        $this->assertNull($messages[0]['consumer_id']);
        $this->assertEquals('default', $messages[0]['queue']);
        $this->assertEquals(0, $messages[0]['priority']);
    }

    public function testCouldSetMessagePriority()
    {
        $connection = $this->createConnection('message_queue');
        $dbal = $connection->getDBALConnection();

        $producer = new DbalMessageProducer($connection);

        // guard
        $messages = $dbal->executeQuery('SELECT * FROM message_queue')->fetchAll();
        $this->assertEmpty($messages);

        // test
        $message = new DbalMessage();
        $message->setPriority(5);

        $producer->send(new DbalDestination('default'), $message);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue')->fetchAll();
        $this->assertCount(1, $messages);
        $this->assertEquals(5, $messages[0]['priority']);

        $message->setPriority(10);
        $producer->send(new DbalDestination('default'), $message);

        $messages = $dbal->executeQuery('SELECT * FROM message_queue ORDER BY id ASC')->fetchAll();
        $this->assertCount(2, $messages);
        $this->assertEquals(10, $messages[1]['priority']);
    }
}
