<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\SearchBundle\Async\Topic\IndexEntityTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class IndexEntityTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new IndexEntityTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'class' => \stdClass::class,
                    'id' => 42,
                ],
                'expectedBody' => [
                    'class' => \stdClass::class,
                    'id' => 42,
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'class' => \stdClass::class,
                    'id' => '42',
                ],
                'expectedBody' => [
                    'class' => \stdClass::class,
                    'id' => '42',
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
                'exceptionMessage' => '/The required options "class", "id" are missing./',
            ],
        ];
    }
}
