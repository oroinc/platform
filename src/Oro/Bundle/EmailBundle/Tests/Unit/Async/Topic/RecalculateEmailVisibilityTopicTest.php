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
            'has email array' => [
                'body' => [
                    'email' => ['test@example.com']
                ],
                'expectedBody' => [
                    'email' => ['test@example.com']
                ]
            ],
            'has email string' => [
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
            'empty emails' => [
                'body' => ['email' => []],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/At least one email should be provided./'
            ],
            'invalid email type' => [
                'body' => ['email' => [1]],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "email" with value array'
                    . ' is expected to be of type "string\[\]" or "string",'
                    . ' but one of the elements is of type "int"./'
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

    public function testCreateJobNameWithEmailsParameter(): void
    {
        self::assertSame(
            'oro.email.recalculate_email_visibility:' . md5('test@test.com,test1@test.com'),
            $this->getTopic()->createJobName(['email' => ['test@test.com', 'test1@test.com']])
        );
    }
}
