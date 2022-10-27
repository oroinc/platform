<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AddEmailAssociationsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new AddEmailAssociationsTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'emailIds' => [42, 142],
                    'targetClass' => \stdClass::class,
                    'targetId' => 1142,
                ],
                'expectedBody' => [
                    'emailIds' => [42, 142],
                    'targetClass' => \stdClass::class,
                    'targetId' => 1142,
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'emailIds' => ['42', '142'],
                    'targetClass' => \stdClass::class,
                    'targetId' => '1142',
                ],
                'expectedBody' => [
                    'emailIds' => ['42', '142'],
                    'targetClass' => \stdClass::class,
                    'targetId' => '1142',
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
                'exceptionMessage' => '/The required options "emailIds", "targetClass", "targetId" are missing./',
            ],
        ];
    }
}
