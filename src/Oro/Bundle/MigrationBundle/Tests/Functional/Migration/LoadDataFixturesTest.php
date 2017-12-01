<?php

namespace Oro\Bundle\MigrationBundle\Tests\Functional\Migration;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group demo-fixtures
 */
class LoadDataFixturesTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testLoadDemoDataFixtures()
    {
        // for manual execution needs reset autoincrements, like that ALTER TABLE `<table_name>` AUTO_INCREMENT=2
        $this->runCommand(LoadDataFixturesCommand::COMMAND_NAME, ['--fixtures-type=demo'], true, true);

        $messages = [];
        foreach ($this->getMessageCollector()->getSentMessages() as $message) {
            $topic = $message['topic'];
            $messages[$topic][] = $message;
        }

        foreach ($messages as $topic => $items) {
            $messages[$topic] = sprintf('Topic: %s, messages: %d', $topic, count($items));
        }

        $this->assertSame([], array_values($messages), 'Message queue must be empty');
    }
}
