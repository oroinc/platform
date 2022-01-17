<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateEmailOwnerAssociationTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateEmailOwnerAssociationTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'jobId' => 42,
                    'ownerClass' => \stdClass::class,
                    'ownerId' => 142,
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'ownerClass' => \stdClass::class,
                    'ownerId' => 142,
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'jobId' => 42,
                    'ownerClass' => \stdClass::class,
                    'ownerId' => '142',
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'ownerClass' => \stdClass::class,
                    'ownerId' => '142',
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
                'exceptionMessage' => '/The required options "jobId", "ownerClass", "ownerId" are missing./',
            ],
        ];
    }
}
