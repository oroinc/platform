<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DatagridExportTopicTest extends AbstractTopicTestCase
{
    private const VALID_GRID_NAME = 'grid-name';
    private const INVALID_GRID_NAME = 'invalid-grid-name';

    private ConfigurationProviderInterface&MockObject $chainConfigurationProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->chainConfigurationProvider = $this->createMock(ConfigurationProviderInterface::class);
        $this->chainConfigurationProvider->expects(self::any())
            ->method('isValidConfiguration')
            ->willReturnMap([
                [self::VALID_GRID_NAME, true],
                [self::INVALID_GRID_NAME, false]
            ]);


        parent::setUp();
    }

    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new DatagridExportTopic($this->chainConfigurationProvider);
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        $jobId = 1;
        $format = 'csv';
        $materializedViewName = 'sample_name';

        return [
            'required only' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 42,
                    ],
                ],
                'expectedBody' => [
                    'jobId' => $jobId,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'gridParameters' => [],
                        FormatterProvider::FORMAT_TYPE => 'excel',
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 42,
                    ],
                    'outputFormat' => $format,
                    'writerBatchSize' => 100,
                ],
            ],
            'all options' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                    'writerBatchSize' => 4242,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'gridParameters' => ['sample-key' => 'sample-value'],
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 42,
                    ],
                ],
                'expectedBody' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                    'writerBatchSize' => 4242,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'gridParameters' => ['sample-key' => 'sample-value'],
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 42,
                        FormatterProvider::FORMAT_TYPE => 'excel',
                    ],
                ],
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        $jobId = 1;
        $format = 'csv';
        $materializedViewName = 'sample_name';

        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "jobId", "outputFormat" are missing./',
            ],
            'invalid grid configuration' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                    'writerBatchSize' => 100,
                    'contextParameters' => [
                        'gridName' => self::INVALID_GRID_NAME,
                        'gridParameters' => ['sample-key' => 'sample-value'],
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 10,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/Grid invalid-grid-name configuration is not valid/',
            ],
            '"outputFormat" is not set' => [
                'body' => [
                    'jobId' => $jobId,
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required option "outputFormat" is missing./',
            ],
            'required context parameters are missing' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "contextParameters\[gridName\]", '
                    . '"contextParameters\[materializedViewName\]", "contextParameters\[rowsLimit\]", '
                    . '"contextParameters\[rowsOffset\]" are missing./',
            ],
            'jobId type is invalid' => [
                'body' => [
                    'jobId' => 'sample-string',
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 42,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "jobId" with value "sample-string" is expected to be of type "int"/',
            ],
            'outputFormat type is invalid' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => [],
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 42,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "outputFormat" with value array is expected to be of type "string"/',
            ],
            'writerBatchSize type is invalid' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                    'writerBatchSize' => 'sample-string',
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 42,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "writerBatchSize" with value "sample-string" is expected to be of type "int"/',
            ],
            'contextParameters[formatType] type is invalid' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                    'writerBatchSize' => 4242,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        FormatterProvider::FORMAT_TYPE => [],
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 42,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "contextParameters\[formatType\]" with value array is expected '
                    . 'to be of type "string"/',
            ],
            'contextParameters[materializedViewName] type is invalid' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                    'writerBatchSize' => 4242,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'materializedViewName' => [],
                        'rowsOffset' => 'sample-string',
                        'rowsLimit' => 42,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "contextParameters\[materializedViewName\]" with value array is expected '
                    . 'to be of type "string"/',
            ],
            'contextParameters[rowsOffset] type is invalid' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                    'writerBatchSize' => 4242,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 'sample-string',
                        'rowsLimit' => 42,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "contextParameters\[rowsOffset\]" with value "sample-string" is expected '
                    . 'to be of type "int"/',
            ],
            'contextParameters[rowsLimit] type is invalid' => [
                'body' => [
                    'jobId' => $jobId,
                    'outputFormat' => $format,
                    'writerBatchSize' => 4242,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => 0,
                        'rowsLimit' => 'sample-string',
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "contextParameters\[rowsLimit\]" with value "sample-string" is expected '
                    . 'to be of type "int"/',
            ],
        ];
    }
}
