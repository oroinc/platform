<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ImportTopicTest extends AbstractTopicTestCase
{
    private const BATCH_SIZE = 5000;

    protected function getTopic(): TopicInterface
    {
        return new ImportTopic(self::BATCH_SIZE);
    }

    public function validBodyDataProvider(): array
    {
        $fullOptionsSet = [
            'userId' => 1,
            'jobId' => 1,
            'jobName' => 'foo',
            'process' => 'bar',
            'processorAlias' => 'baz',
            'fileName' => 'file_name',
            'originFileName' => 'original_file_name',
            'options' => [
                Context::OPTION_BATCH_SIZE => 100,
                'batch_number' => 2,
                'attempts' => 1,
                'max_attempts' => 5,
                'incremented_read' => false,
            ],
            'attempts' => 1,
        ];

        return [
            'only required options' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => 'baz',
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                ],
                'expectedBody' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => 'baz',
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                    'options' => [
                        Context::OPTION_BATCH_SIZE => self::BATCH_SIZE,
                    ],
                ],
            ],
            'full set of options' => [
                'body' => $fullOptionsSet,
                'expectedBody' => $fullOptionsSet,
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "fileName", "jobId", "jobName", "originFileName", "process", '
                    . '"processorAlias", "userId" are missing./',
            ],
            'wrong userId type' => [
                'body' => [
                    'userId' => null,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => 'baz',
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "userId" with value null is expected to be of type "int"/',
            ],
            'wrong jobId type' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => null,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => 'baz',
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value null is expected to be of type "int"/',
            ],
            'wrong jobName type' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => null,
                    'process' => 'bar',
                    'processorAlias' => 'baz',
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "jobName" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong process type' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => null,
                    'processorAlias' => 'bar',
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "process" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong processorAlias type' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => null,
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "processorAlias" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong fileName type' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => 'baz',
                    'fileName' => null,
                    'originFileName' => 'original_file_name',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "fileName" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong originFileName type' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => 'baz',
                    'fileName' => 'file_name',
                    'originFileName' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "originFileName" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong options type' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => 'baz',
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                    'options' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "options" with value null is expected to be of type "array", '
                    . 'but is of type "null"./',
            ],
            'wrong options batchSize type' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => 'baz',
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                    'options' => [
                        Context::OPTION_BATCH_SIZE => null,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => sprintf(
                    '/The option "options\[%s\]" is expected to be of type "int"./',
                    Context::OPTION_BATCH_SIZE
                ),
            ],
            'wrong attempts type' => [
                'body' => [
                    'userId' => 1,
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'process' => 'bar',
                    'processorAlias' => 'bar',
                    'fileName' => 'file_name',
                    'originFileName' => 'original_file_name',
                    'attempts' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "attempts" with value null is expected to be of type "int", '
                    . 'but is of type "null"./',
            ],
        ];
    }
}
