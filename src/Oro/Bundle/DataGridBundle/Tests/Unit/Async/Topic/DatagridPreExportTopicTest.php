<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DatagridPreExportTopicTest extends AbstractTopicTestCase
{
    private const VALID_GRID_NAME = 'grid-name';
    private const INVALID_GRID_NAME = 'invalid-grid-name';

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
        return new DatagridPreExportTopic(
            4242,
            $this->createMock(TokenAccessorInterface::class),
            $this->chainConfigurationProvider
        );
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        $format = 'csv';

        return [
            'required only' => [
                'body' => [
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                    ],
                ],
                'expectedBody' => [
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'gridParameters' => [
                            ParameterBag::DATAGRID_MODES_PARAMETER => [
                                DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE,
                            ],
                        ],
                        FormatterProvider::FORMAT_TYPE => 'excel',
                    ],
                    'notificationTemplate' => 'datagrid_export_result',
                    'batchSize' => 4242,
                ],
            ],
            'all options' => [
                'body' => [
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'gridParameters' => [
                            ParameterBag::DATAGRID_MODES_PARAMETER => [
                                'sample-mode',
                            ],
                            'param1' => 'value1',
                            'param2' => 2,
                        ],
                        FormatterProvider::FORMAT_TYPE => 'typeFoo',
                    ],
                    'notificationTemplate' => 'notification-template',
                    'batchSize' => 101,
                ],
                'expectedBody' => [
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'gridParameters' => [
                            ParameterBag::DATAGRID_MODES_PARAMETER => [
                                'sample-mode',
                                DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE,
                            ],
                            'param1' => 'value1',
                            'param2' => 2,
                        ],
                        FormatterProvider::FORMAT_TYPE => 'typeFoo',
                    ],
                    'notificationTemplate' => 'notification-template',
                    'batchSize' => 101,
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        $format = 'csv';

        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "outputFormat" is missing./',
            ],
            'invalid grid configuration' => [
                'body' => [
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => self::INVALID_GRID_NAME,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/Grid invalid-grid-name configuration is not valid/',
            ],
            'required context parameters are missing' => [
                'body' => [
                    'outputFormat' => 'csv',
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required option "contextParameters\[gridName\]" is missing./',
            ],
            'outputFormat type is invalid' => [
                'body' => [
                    'outputFormat' => [],
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "outputFormat" with value array is expected to be of type "string"/',
            ],
            'batchSize type is invalid' => [
                'body' => [
                    'outputFormat' => $format,
                    'batchSize' => 'sample-string',
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "batchSize" with value "sample-string" is expected to be of type "int"/',
            ],
            'contextParameters[formatType] type is invalid' => [
                'body' => [
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        FormatterProvider::FORMAT_TYPE => [],
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "contextParameters\[formatType\]" with value array is expected '
                    . 'to be of type "string"/',
            ],
        ];
    }
}
