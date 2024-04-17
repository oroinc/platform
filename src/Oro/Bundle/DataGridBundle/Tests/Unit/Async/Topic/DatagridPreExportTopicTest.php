<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DatagridPreExportTopicTest extends AbstractTopicTestCase
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
        $topic = new DatagridPreExportTopic();
        $topic->setConfigurationProvider($this->configurationProvider);

        return $topic;
    }

    public function validBodyDataProvider(): array
    {
        $format = 'csv';

        return [
            'required only' => [
                'body' => [
                    'format' => $format,
                    'parameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                    ],
                ],
                'expectedBody' => [
                    'format' => $format,
                    'parameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'gridParameters' => [],
                        FormatterProvider::FORMAT_TYPE => 'excel',
                    ],
                    'notificationTemplate' => null,
                ],
            ],
            'all options' => [
                'body' => [
                    'format' => $format,
                    'parameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'gridParameters' => [
                            'param1' => 'value1',
                            'param2' => 2,
                        ],
                        'pageSize' => 4242,
                        'exportByPages' => true,
                        FormatterProvider::FORMAT_TYPE => 'typeFoo',
                    ],
                    'notificationTemplate' => 'notification-template',
                ],
                'expectedBody' => [
                    'format' => $format,
                    'parameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'gridParameters' => [
                            'param1' => 'value1',
                            'param2' => 2,
                        ],
                        'pageSize' => 4242,
                        'exportByPages' => true,
                        FormatterProvider::FORMAT_TYPE => 'typeFoo',
                    ],
                    'notificationTemplate' => 'notification-template',
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        $gridName = 'grid-name';

        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "format" is missing./',
            ],
            '"gridName" is not set' => [
                'body' => [
                    'format' => 'csv',
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required option "parameters\[gridName\]" is missing./',
            ],
            '"pageSize" is not numeric' => [
                'body' => [
                    'format' => 'csv',
                    'parameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'pageSize' => 'invalid',
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "parameters\[pageSize\]" with value "invalid" is expected to be of type "numeric", '
                    . 'but is of type "string"./',
            ],
            '"exportByPages" is not boolean' => [
                'body' => [
                    'format' => 'csv',
                    'parameters' => [
                        'gridName' => self::VALID_GRID_NAME,
                        'exportByPages' => 'invalid',
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "parameters\[exportByPages\]" with value "invalid" is expected to be of type '
                    . '"boolean", but is of type "string"./',
            ],
            'gridName is not valid ' => [
                'body' => [
                    'format' => 'csv',
                    'parameters' => [
                        'gridName' => self::INVALID_GRID_NAME,
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    sprintf('/Grid %s configuration is not valid/', self::INVALID_GRID_NAME),
            ],
        ];
    }
}
