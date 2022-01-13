<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByTypeTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class IndexEntitiesByTypeTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new IndexEntitiesByTypeTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'jobId' => 42,
                    'entityClass' => \stdClass::class,
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'entityClass' => \stdClass::class,
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
                'exceptionMessage' => '/The required options "entityClass", "jobId" are missing./',
            ],
        ];
    }
}
