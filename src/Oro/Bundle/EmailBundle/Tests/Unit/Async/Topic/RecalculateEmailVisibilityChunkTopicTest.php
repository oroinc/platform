<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityChunkTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RecalculateEmailVisibilityChunkTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new RecalculateEmailVisibilityChunkTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'has ids' => [
                'body' => [
                    'jobId' => 1,
                    'ids' => [1, 2, 3]
                ],
                'expectedBody' => [
                    'jobId' => 1,
                    'ids' => [1, 2, 3]
                ]
            ]
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "ids", "jobId" are missing./'
            ],
            'invalid jobId type' => [
                'body' => ['jobId' => '1', 'ids' => [5,8]],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value "1" is expected to be of type "int",'
                    . ' but is of type "string"./'
            ],
            'invalid ids type' => [
                'body' => ['jobId' => 1, 'ids' => 4],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "ids" with value 4'
                    . ' is expected to be of type "string\[\]" or "int\[\]",'
                    . ' but is of type "int"./'
            ]
        ];
    }
}
