<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RecalculateEmailVisibilityTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new RecalculateEmailVisibilityTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'has email' => [
                'body' => [
                    'email' => 'test@example.com'
                ],
                'expectedBody' => [
                    'email' => 'test@example.com'
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
                'exceptionMessage' => '/The required option "email" is missing./'
            ],
            'empty email' => [
                'body' => ['email' => ''],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The "email" was expected to be not empty./'
            ],
            'invalid email type' => [
                'body' => ['email' => 1],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "email" with value 1 is expected to be of type "string",'
                    . ' but is of type "int"./'
            ]
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.email.recalculate_email_visibility:' . md5('test@test.com'),
            $this->getTopic()->createJobName(['email' => 'test@test.com'])
        );
    }
}
