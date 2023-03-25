<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class IndexEntitiesByIdTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new IndexEntitiesByIdTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'class' => \stdClass::class,
                    'entityIds' => [42, 142],
                ],
                'expectedBody' => [
                    'class' => \stdClass::class,
                    'entityIds' => [42, 142],
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'class' => \stdClass::class,
                    'entityIds' => ['42', '142'],
                ],
                'expectedBody' => [
                    'class' => \stdClass::class,
                    'entityIds' => ['42', '142'],
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "class", "entityIds" are missing./',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        $messageBody = [
            'class' => 'class-name',
            'entityIds' => ['42', '142'],
        ];
        self::assertSame(
            'search_reindex|'. md5(serialize($messageBody['class']) . serialize($messageBody['entityIds'])),
            $this->getTopic()->createJobName($messageBody)
        );
    }
}
