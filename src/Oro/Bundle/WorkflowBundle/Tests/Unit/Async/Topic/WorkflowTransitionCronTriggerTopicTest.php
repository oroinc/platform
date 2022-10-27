<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\WorkflowBundle\Async\Topic\WorkflowTransitionCronTriggerTopic;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class WorkflowTransitionCronTriggerTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new WorkflowTransitionCronTriggerTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    TransitionTriggerMessage::TRANSITION_TRIGGER => 42,
                    TransitionTriggerMessage::MAIN_ENTITY => 4242,
                ],
                'expectedBody' => [
                    TransitionTriggerMessage::TRANSITION_TRIGGER => 42,
                    TransitionTriggerMessage::MAIN_ENTITY => 4242,
                ],
            ],
            'required only when mainEntity is string' => [
                'body' => [
                    TransitionTriggerMessage::TRANSITION_TRIGGER => 42,
                    TransitionTriggerMessage::MAIN_ENTITY => '4242',
                ],
                'expectedBody' => [
                    TransitionTriggerMessage::TRANSITION_TRIGGER => 42,
                    TransitionTriggerMessage::MAIN_ENTITY => '4242',
                ],
            ],
            'required only when mainEntity is array' => [
                'body' => [
                    TransitionTriggerMessage::TRANSITION_TRIGGER => 42,
                    TransitionTriggerMessage::MAIN_ENTITY => ['id' => 4242],
                ],
                'expectedBody' => [
                    TransitionTriggerMessage::TRANSITION_TRIGGER => 42,
                    TransitionTriggerMessage::MAIN_ENTITY => ['id' => 4242],
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
                'exceptionMessage' => '/The required options "mainEntity", "transitionTrigger" are missing./',
            ],
            'transitionTrigger has invalid type' => [
                'body' => [
                    TransitionTriggerMessage::TRANSITION_TRIGGER => new \stdClass(),
                    TransitionTriggerMessage::MAIN_ENTITY => 4242,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "transitionTrigger" with value stdClass is expected '
                    . 'to be of type "int"/',
            ],
            'mainEntity has invalid type' => [
                'body' => [
                    TransitionTriggerMessage::TRANSITION_TRIGGER => 42,
                    TransitionTriggerMessage::MAIN_ENTITY => new \stdClass(),
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "mainEntity" with value stdClass is expected '
                    . 'to be of type "int" or "string" or "array"/',
            ],
        ];
    }
}
