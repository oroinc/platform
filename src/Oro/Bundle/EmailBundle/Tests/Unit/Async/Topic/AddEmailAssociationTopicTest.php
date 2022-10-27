<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AddEmailAssociationTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new AddEmailAssociationTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'jobId' => 42,
                    'emailId' => 142,
                    'targetClass' => \stdClass::class,
                    'targetId' => 11142,
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'emailId' => 142,
                    'targetClass' => \stdClass::class,
                    'targetId' => 11142,
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'jobId' => 42,
                    'emailId' => '142',
                    'targetClass' => \stdClass::class,
                    'targetId' => '11142',
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'emailId' => '142',
                    'targetClass' => \stdClass::class,
                    'targetId' => '11142',
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
                'exceptionMessage' => '/The required options "emailId", "jobId", "targetClass", '
                    . '"targetId" are missing./',
            ],
        ];
    }
}
