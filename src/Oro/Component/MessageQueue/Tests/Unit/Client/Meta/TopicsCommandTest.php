<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

use Oro\Component\MessageQueue\Client\Meta\TopicMeta;
use Oro\Component\MessageQueue\Client\Meta\TopicsCommand;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;

class TopicsCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var TopicsCommand */
    private $command;

    /** @var Container */
    private $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $topicRegistry;

    protected function setUp()
    {
        $this->topicRegistry = $this->createMock(TopicMetaRegistry::class);

        $this->command = new TopicsCommand();

        $this->container = new Container();
        $this->container->set('oro_message_queue.client.meta.topic_meta_registry', $this->topicRegistry);
        $this->command->setContainer($this->container);
    }

    public function testShouldShowMessageFoundZeroTopicsIfAnythingInRegistry()
    {
        $this->topicRegistry->expects(self::once())
            ->method('getTopicsMeta')
            ->willReturn([]);

        $output = $this->executeCommand();

        $this->assertContains('Found 0 topics', $output);
    }

    public function testShouldShowMessageFoundTwoTopics()
    {
        $this->topicRegistry->expects(self::once())
            ->method('getTopicsMeta')
            ->willReturn([
                new TopicMeta('fooTopic'),
                new TopicMeta('barTopic'),
            ]);

        $output = $this->executeCommand();

        $this->assertContains('Found 2 topics', $output);
    }

    public function testShouldShowInfoAboutTopics()
    {
        $this->topicRegistry->expects(self::once())
            ->method('getTopicsMeta')
            ->willReturn([
                new TopicMeta('fooTopic', 'fooDescription', ['fooSubscriber']),
                new TopicMeta('barTopic', 'barDescription', ['barSubscriber']),
            ]);

        $output = $this->executeCommand();

        $this->assertContains('fooTopic', $output);
        $this->assertContains('fooDescription', $output);
        $this->assertContains('fooSubscriber', $output);
        $this->assertContains('barTopic', $output);
        $this->assertContains('barDescription', $output);
        $this->assertContains('barSubscriber', $output);
    }

    /**
     * @param string[] $arguments
     *
     * @return string
     */
    protected function executeCommand(array $arguments = [])
    {
        $tester = new CommandTester($this->command);
        $tester->execute($arguments);

        return $tester->getDisplay();
    }
}
