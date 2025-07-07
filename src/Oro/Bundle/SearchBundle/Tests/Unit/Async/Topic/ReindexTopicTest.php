<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\SearchBundle\Async\Topic\ReindexTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class ReindexTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new ReindexTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'expectedBody' => [],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'invalid option' => [
                'body' => ['invalid_key' => 'invalid_value'],
                'exceptionClass' => UndefinedOptionsException::class,
                'exceptionMessage' => '/The option "invalid_key" does not exist./',
            ],
        ];
    }

    /**
     * @dataProvider createJobNameDataProvider
     */
    public function testCreateJobName(array $messageBody, string $expectedJobName): void
    {
        self::assertEquals(
            $expectedJobName,
            $this->getTopic()->createJobName($messageBody)
        );
    }

    public static function createJobNameDataProvider(): array
    {
        return [
            'full' => [[], 'oro.search.reindex'],
            'one entity' => [
                ['Test\Entity'],
                'oro.search.reindex:Test\Entity'
            ],
            'several entities' => [
                ['Test\Entity1', 'Test\Entity2'],
                'oro.search.reindex:' . hash('sha256', 'Test\Entity1,Test\Entity2')
            ],
            'several entities, not ordered' => [
                ['Test\Entity2', 'Test\Entity1'],
                'oro.search.reindex:' . hash('sha256', 'Test\Entity1,Test\Entity2')
            ],
        ];
    }
}
