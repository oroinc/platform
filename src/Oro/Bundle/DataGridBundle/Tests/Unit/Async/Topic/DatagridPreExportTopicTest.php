<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DatagridPreExportTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new DatagridPreExportTopic();
    }

    public function validBodyDataProvider(): array
    {
        $format = 'csv';
        $gridName = 'grid-name';

        return [
            'required only' => [
                'body' => [
                    'format' => $format,
                    'parameters' => [
                        'gridName' => $gridName,
                    ],
                ],
                'expectedBody' => [
                    'format' => $format,
                    'parameters' => [
                        'gridName' => $gridName,
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
                        'gridName' => $gridName,
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
                        'gridName' => $gridName,
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
                        'gridName' => $gridName,
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
                        'gridName' => $gridName,
                        'exportByPages' => 'invalid',
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "parameters\[exportByPages\]" with value "invalid" is expected to be of type '
                    . '"boolean", but is of type "string"./',
            ],
        ];
    }
}
