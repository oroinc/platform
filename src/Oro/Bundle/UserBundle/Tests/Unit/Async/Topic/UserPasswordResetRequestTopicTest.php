<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\UserBundle\Async\Topic\UserPasswordResetRequestTopic as Topic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UserPasswordResetRequestTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): Topic
    {
        return new Topic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'username' => [
                'body' => [Topic::USER_IDENTIFIER => 'john'],
                'expectedBody' => [Topic::USER_IDENTIFIER => 'john'],
            ],
            'email' => [
                'body' => [Topic::USER_IDENTIFIER => 'john@example.com'],
                'expectedBody' => [Topic::USER_IDENTIFIER => 'john@example.com'],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "userIdentifier" is missing./',
            ],
            'userIdentifier has invalid type' => [
                'body' => [Topic::USER_IDENTIFIER => ['john']],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "userIdentifier" with value array is expected '
                    . 'to be of type "string"/',
            ],
        ];
    }
}
