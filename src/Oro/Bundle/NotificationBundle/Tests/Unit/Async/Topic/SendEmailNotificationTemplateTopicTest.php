<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SendEmailNotificationTemplateTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SendEmailNotificationTemplateTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'from' => 'from@example.org',
                    'recipientUserId' => 42,
                    'template' => 'sample_template',
                ],
                'expectedBody' => [
                    'from' => 'from@example.org',
                    'recipientUserId' => 42,
                    'template' => 'sample_template',
                    'templateParams' => [],
                    'templateEntity' => null,
                ],
            ],
            'all options' => [
                'body' => [
                    'from' => 'from@example.org',
                    'recipientUserId' => 42,
                    'template' => 'sample_template',
                    'templateEntity' => \stdClass::class,
                    'templateParams' => ['sample_key' => 'sample_value'],
                ],
                'expectedBody' => [
                    'from' => 'from@example.org',
                    'recipientUserId' => 42,
                    'template' => 'sample_template',
                    'templateEntity' => \stdClass::class,
                    'templateParams' => ['sample_key' => 'sample_value'],
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
                'exceptionMessage' => '/The required options "from", "recipientUserId", "template" are missing./',
            ],
            'from must be a valid email address' => [
                'body' => [
                    'from' => 'invalid',
                    'template' => 'sample_template',
                    'recipientUserId' => 42,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "from" with value "invalid" is invalid./',
            ],
        ];
    }
}
