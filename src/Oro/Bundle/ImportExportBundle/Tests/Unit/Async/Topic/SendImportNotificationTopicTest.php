<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\Topic\SendImportNotificationTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SendImportNotificationTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SendImportNotificationTopic();
    }

    public function validBodyDataProvider(): array
    {
        $requiredOptionsSet = [
            'rootImportJobId' => 1,
            'process' => 'foo',
            'userId' => 1,
            'originFileName' => 'bar.csv',
        ];

        return [
            'only required options' => [
                'body' => $requiredOptionsSet,
                'expectedBody' => $requiredOptionsSet,
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "originFileName", "process", "rootImportJobId", "userId" are missing./',
            ],
            'wrong rootImportJobId type' => [
                'body' => [
                    'rootImportJobId' => null,
                    'process' => 'foo',
                    'userId' => 1,
                    'originFileName' => 'bar.csv',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "rootImportJobId" with value null is expected to be of type "int"/',
            ],
            'wrong process type' => [
                'body' => [
                    'rootImportJobId' => 1,
                    'process' => null,
                    'userId' => 1,
                    'originFileName' => 'bar.csv',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "process" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong userId type' => [
                'body' => [
                    'rootImportJobId' => 1,
                    'process' => 'foo',
                    'userId' => null,
                    'originFileName' => 'bar.csv',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "userId" with value null is expected to be of type "int"/',
            ],
            'wrong originFileName type' => [
                'body' => [
                    'rootImportJobId' => 1,
                    'process' => 'foo',
                    'userId' => 1,
                    'originFileName' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "originFileName" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
        ];
    }
}
