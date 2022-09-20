<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class PostExportTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new PostExportTopic();
    }

    public function validBodyDataProvider(): array
    {
        $onlyRequiredOptionsSet = [
            'jobId' => 1,
            'jobName' => 'foo',
            'exportType' => ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
            'outputFormat' => 'output_format',
            'recipientUserId' => 1,
            'entity' => 'entityName',
        ];
        $fullOptionsSet = array_merge(
            $onlyRequiredOptionsSet,
            [
                'notificationTemplate' => ImportExportResultSummarizer::TEMPLATE_EXPORT_RESULT,
            ]
        );

        return [
            'only required options' => [
                'body' => $onlyRequiredOptionsSet,
                'expectedBody' => $fullOptionsSet,
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
                    '/The required options "entity", "exportType", "jobId", "jobName", "outputFormat", '
                    . '"recipientUserId" are missing./',
            ],
            'wrong jobId type' => [
                'body' => [
                    'jobId' => null,
                    'jobName' => 'foo',
                    'exportType' => ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
                    'outputFormat' => 'output_format',
                    'recipientUserId' => 1,
                    'entity' => 'entityName',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value null is expected to be of type "int"/',
            ],
            'wrong jobName type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => null,
                    'exportType' => ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
                    'outputFormat' => 'output_format',
                    'recipientUserId' => 1,
                    'entity' => 'entityName',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "jobName" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong exportType type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'exportType' => null,
                    'outputFormat' => 'output_format',
                    'recipientUserId' => 1,
                    'entity' => 'entityName',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "exportType" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong outputFormat type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'exportType' => ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
                    'outputFormat' => null,
                    'recipientUserId' => 1,
                    'entity' => 'entityName',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "outputFormat" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong recipientUserId type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'exportType' => ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
                    'outputFormat' => 'output_format',
                    'recipientUserId' => null,
                    'entity' => 'entityName',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "recipientUserId" with value null is expected to be of type "int"/',
            ],
            'wrong entity type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'exportType' => ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
                    'outputFormat' => 'output_format',
                    'recipientUserId' => 1,
                    'entity' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "entity" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong notificationTemplate type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'exportType' => ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
                    'outputFormat' => 'output_format',
                    'recipientUserId' => 1,
                    'entity' => 'entityName',
                    'notificationTemplate' => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "notificationTemplate" with value array is expected to be of type "string" or "null", '
                    . 'but is of type "array"./',
            ],
        ];
    }
}
