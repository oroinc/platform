<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\TopicMeta;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;
use Oro\Component\MessageQueue\Client\Meta\TopicsCommand;
use Symfony\Component\Console\Tester\CommandTester;

class TopicsCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var TopicsCommand */
    private $command;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $topicRegistry;

    protected function setUp(): void
    {
        $this->topicRegistry = $this->createMock(TopicMetaRegistry::class);

        $this->command = new TopicsCommand($this->topicRegistry);
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
