<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SendEmailNotificationTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SendEmailNotificationTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'from' => 'from@example.org',
                    'toEmail' => 'to@example.org',
                    'subject' => 'Sample subject',
                    'body' => 'Sample body',
                ],
                'expectedBody' => [
                    'from' => 'from@example.org',
                    'toEmail' => 'to@example.org',
                    'subject' => 'Sample subject',
                    'body' => 'Sample body',
                    'contentType' => 'text/plain',
                ],
            ],
            'all options' => [
                'body' => [
                    'from' => 'from@example.org',
                    'toEmail' => 'to@example.org',
                    'subject' => 'Sample subject',
                    'body' => 'Sample body',
                    'contentType' => 'text/html',
                ],
                'expectedBody' => [
                    'from' => 'from@example.org',
                    'toEmail' => 'to@example.org',
                    'subject' => 'Sample subject',
                    'body' => 'Sample body',
                    'contentType' => 'text/html',
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
                'exceptionMessage' => '/The required options "body", "from", "subject", "toEmail" are missing./',
            ],
            'from must be a valid email address' => [
                'body' => [
                    'from' => 'invalid',
                    'toEmail' => 'to@example.org',
                    'subject' => 'Sample subject',
                    'body' => 'Sample body',
                    'contentType' => 'text/html',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "from" with value "invalid" is invalid./',
            ],
            'toEmail must be a valid email address' => [
                'body' => [
                    'from' => 'from@example.org',
                    'toEmail' => 'invalid',
                    'subject' => 'Sample subject',
                    'body' => 'Sample body',
                    'contentType' => 'text/html',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "toEmail" with value "invalid" is invalid./',
            ],
        ];
    }
}
