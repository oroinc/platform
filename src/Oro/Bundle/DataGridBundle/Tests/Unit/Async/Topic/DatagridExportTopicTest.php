<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DatagridExportTopicTest extends AbstractTopicTestCase
{
    private const VALID_GRID_NAME = 'grid-name';

    private const INVALID_GRID_NAME = 'invalid-grid-name';

    private ConfigurationProviderInterface $configurationProvider;

    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ConfigurationProviderInterface::class);

        $this->configurationProvider
            ->method('getConfiguration')
            ->willReturnCallback(function ($gridName) {
                if ($gridName === self::INVALID_GRID_NAME) {
                    throw new RuntimeException(sprintf('Grid %s configuration is not valid', $gridName));
                }

                return DatagridConfiguration::createNamed($gridName, ['extend_entity_name' => \stdClass::class]);
            });

        parent::setUp();
    }

    protected function getTopic(): TopicInterface
    {
        $topic = new DatagridExportTopic();
        $topic->setConfigurationProvider($this->configurationProvider);

        return $topic;
    }

    public function validBodyDataProvider(): array
    {
        $jobId = 1;
        $batchSize = 200;
        $format = 'csv';
        $formatType = 'format-type';
        $gridName = self::VALID_GRID_NAME;
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
                        'exactPage' => 42,
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
                        'exactPage' => 42,
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
                    'outputFormat' => 'bar',
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required option "parameters\[gridName\]" is missing./',
            ],
            'gridName is not valid ' => [
                'body' => [
                    'jobId' => 1,
                    'format' => 'csv',
                    'parameters' => [
                        'gridName' => self::INVALID_GRID_NAME,
                    ],
                    'entity' => \stdClass::class,
                    'jobName' => self::INVALID_GRID_NAME,
                    'outputFormat' => 'bar',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    sprintf('/Grid %s configuration is not valid/', self::INVALID_GRID_NAME),
            ],
            '"exactPage" is not numeric' => [
                'body' => [
                    'jobId' => 1,
                    'format' => 'csv',
                    'jobName' => 'foo',
                    'entity' => \stdClass::class,
                    'outputFormat' => 'bar',
                    'parameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'exactPage' => 'invalid',
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "parameters\[exactPage\]" with value "invalid" is expected to be of type "numeric", '
                    . 'but is of type "string"./',
            ],
        ];
    }
}
