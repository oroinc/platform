<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByRangeTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class IndexEntitiesByRangeTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new IndexEntitiesByRangeTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'jobId' => 42,
                    'entityClass' => \stdClass::class,
                    'offset' => 142,
                    'limit' => 1142,
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'entityClass' => \stdClass::class,
                    'offset' => 142,
                    'limit' => 1142,
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
                'exceptionMessage' => '/The required options "entityClass", "jobId", "limit", "offset" are missing./',
            ],
        ];
    }
}
