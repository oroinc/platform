<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\TopicDescriptionProvider;
use Oro\Component\MessageQueue\Client\Meta\TopicMeta;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;
use Oro\Component\MessageQueue\Client\Meta\TopicsCommand;
use Symfony\Component\Console\Tester\CommandTester;

class TopicsCommandTest extends \PHPUnit\Framework\TestCase
{
    private TopicMetaRegistry|\PHPUnit\Framework\MockObject\MockObject $topicRegistry;

    private TopicDescriptionProvider|\PHPUnit\Framework\MockObject\MockObject $topicDescriptionProvider;

    private TopicsCommand $command;


    protected function setUp(): void
    {
        $this->topicRegistry = $this->createMock(TopicMetaRegistry::class);
        $this->topicDescriptionProvider = $this->createMock(TopicDescriptionProvider::class);

        $this->command = new TopicsCommand($this->topicRegistry, $this->topicDescriptionProvider);
    }

    public function testShouldShowMessageFoundZeroTopicsIfAnythingInRegistry(): void
    {
        $this->topicRegistry->expects(self::once())
            ->method('getTopicsMeta')
            ->willReturn([]);

        $output = $this->executeCommand();

        self::assertStringContainsString('Found 0 topics', $output);
    }

    public function testShouldShowMessageFoundTwoTopics(): void
    {
        $this->topicRegistry->expects(self::once())
            ->method('getTopicsMeta')
            ->willReturn(
                [
                    new TopicMeta('sample_topic1'),
                    new TopicMeta('sample_topic2'),
                ]
            );

        $output = $this->executeCommand();

        self::assertStringContainsString('Found 2 topics', $output);
    }

    public function testShouldShowInfoAboutTopics(): void
    {
        $this->topicRegistry
            ->expects(self::once())
            ->method('getTopicsMeta')
            ->willReturn(
                [
                    new TopicMeta('sample_topic1', ['sample_queue'], ['sample_queue' => 'sample_processor1']),
                    new TopicMeta('sample_topic2', ['sample_queue'], ['sample_queue' => 'sample_processor2']),
                ]
            );

        $this->topicDescriptionProvider
            ->expects(self::exactly(2))
            ->method('getTopicDescription')
            ->willReturnMap(
                [
                    ['sample_topic1', 'sample_description1'],
                    ['sample_topic2', 'sample_description2'],
                ]
            );

        $output = $this->executeCommand();

        self::assertStringContainsString('sample_topic1', $output);
        self::assertStringContainsString('sample_description1', $output);
        self::assertStringContainsString('sample_processor1', $output);
        self::assertStringContainsString('sample_topic2', $output);
        self::assertStringContainsString('sample_description2', $output);
        self::assertStringContainsString('sample_processor2', $output);
    }

    /**
     * @param string[] $arguments
     *
     * @return string
     */
    private function executeCommand(array $arguments = []): string
    {
        $tester = new CommandTester($this->command);
        $tester->execute($arguments);

        return $tester->getDisplay();
    }
}
