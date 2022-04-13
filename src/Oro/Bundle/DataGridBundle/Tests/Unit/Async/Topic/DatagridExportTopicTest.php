<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DatagridExportTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new DatagridExportTopic();
    }

    public function validBodyDataProvider(): array
    {
        $jobId = 1;
        $batchSize = 200;
        $format = 'csv';
        $formatType = 'format-type';
        $gridName = 'grid-name';
        $entityName = \stdClass::class;
        $notificationTemplate = 'notification-template';
        $gridParameters = [
            'param1' => 'value1',
            'param2' => 2,
        ];

        return [
            'required only' => [
                'body' => [
                    'jobId' => $jobId,
                    'format' => $format,
                    'parameters' => [
                        'gridName' => $gridName,
                    ],
                    'entity' => $entityName,
                    'jobName' => $gridName,
                    'outputFormat' => $format,
                ],
                'expectedBody' => [
                    'jobId' => $jobId,
                    'format' => $format,
                    'parameters' => [
                        'gridName' => $gridName,
                        'gridParameters' => [],
                        FormatterProvider::FORMAT_TYPE => 'excel',
                    ],
                    'exportType' => ProcessorRegistry::TYPE_EXPORT,
                    'notificationTemplate' => null,
                    'entity' => $entityName,
                    'jobName' => $gridName,
                    'outputFormat' => $format,
                    'batchSize' => null,
                ],
            ],
            'all options' => [
                'body' => [
                    'jobId' => $jobId,
                    'format' => $format,
                    'parameters' => [
                        'gridName' => $gridName,
                        'gridParameters' => $gridParameters,
                        FormatterProvider::FORMAT_TYPE => $formatType,
                    ],
                    'exportType' => ProcessorRegistry::TYPE_EXPORT,
                    'entity' => $entityName,
                    'jobName' => $gridName,
                    'outputFormat' => $format,
                    'notificationTemplate' => $notificationTemplate,
                    'batchSize' => $batchSize,
                ],
                'expectedBody' => [
                    'jobId' => $jobId,
                    'format' => $format,
                    'parameters' => [
                        'gridName' => $gridName,
                        'gridParameters' => $gridParameters,
                        FormatterProvider::FORMAT_TYPE => $formatType,
                    ],
                    'exportType' => ProcessorRegistry::TYPE_EXPORT,
                    'entity' => $entityName,
                    'jobName' => $gridName,
                    'outputFormat' => $format,
                    'notificationTemplate' => $notificationTemplate,
                    'batchSize' => $batchSize,
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
                'exceptionMessage' =>
                    '/The required options "entity", "format", "jobId", "jobName", "outputFormat" are missing./',
            ],
            '"gridName" is not set' => [
                'body' => [
                    'jobId' => 1,
                    'format' => 'csv',
                    'jobName' => 'foo',
                    'entity' => \stdClass::class,
                    'outputFormat' => 'bar'
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required option "parameters\[gridName\]" is missing./',
            ],
        ];
    }
}
