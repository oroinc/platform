<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\SendEmailTemplateTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SendEmailTemplateTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SendEmailTemplateTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'from' => 'from@example.org',
                    'templateName' => 'sample_template',
                    'recipients' => ['to1@example.org', 'to2@example.org'],
                    'entity' => [\stdClass::class, 42],
                ],
                'expectedBody' => [
                    'from' => 'from@example.org',
                    'templateName' => 'sample_template',
                    'recipients' => ['to1@example.org', 'to2@example.org'],
                    'entity' => [\stdClass::class, 42],
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
                'exceptionMessage' => '/The required options "entity", "from", "recipients", '
                    . '"templateName" are missing./',
            ],
            'entity must contain class and id' => [
                'body' => [
                    'from' => 'from@example.org',
                    'templateName' => 'sample_template',
                    'recipients' => ['to1@example.org', 'to2@example.org'],
                    'entity' => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/Parameter "entity" must be an array \[string \$entityClass, int \$entityId\], '
                    . 'got "\[\]"./',
            ],
            'from must a valid email address' => [
                'body' => [
                    'from' => 'invalid',
                    'templateName' => 'sample_template',
                    'recipients' => ['to1@example.org', 'to2@example.org'],
                    'entity' => [\stdClass::class, 42],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "from" with value "invalid" is invalid./',
            ],
            'recipients must not be empty' => [
                'body' => [
                    'from' => 'from@example.org',
                    'templateName' => 'sample_template',
                    'recipients' => [],
                    'entity' => [\stdClass::class, 42],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/Parameter "recipients" must contain at least one email address/',
            ],
            'recipients must contain valid email addressees' => [
                'body' => [
                    'from' => 'from@example.org',
                    'templateName' => 'sample_template',
                    'recipients' => ['to1@example.org', 'invalid'],
                    'entity' => [\stdClass::class, 42],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/Parameter "recipients" contains invalid email address: "invalid"/',
            ],
        ];
    }
}
