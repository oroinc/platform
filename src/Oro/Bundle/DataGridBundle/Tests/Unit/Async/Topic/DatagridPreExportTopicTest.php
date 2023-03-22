<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DatagridPreExportTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new DatagridPreExportTopic(4242, $this->createMock(TokenAccessorInterface::class));
    }

    public function validBodyDataProvider(): array
    {
        $format = 'csv';
        $gridName = 'grid-name';

        return [
            'required only' => [
                'body' => [
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => $gridName,
                    ],
                ],
                'expectedBody' => [
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => $gridName,
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
                        'gridName' => $gridName,
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
                        'gridName' => $gridName,
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

    public function invalidBodyDataProvider(): array
    {
        $format = 'csv';
        $gridName = 'grid-name';

        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "outputFormat" is missing./',
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
                        'gridName' => $gridName,
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
                        'gridName' => $gridName,
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
                        'gridName' => $gridName,
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
