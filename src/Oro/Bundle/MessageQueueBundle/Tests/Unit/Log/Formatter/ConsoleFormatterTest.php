<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Formatter;

use Oro\Bundle\MessageQueueBundle\Log\Formatter\ConsoleFormatter;
use Oro\Component\MessageQueue\Client\Config;

class ConsoleFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider recordsProvider
     *
     * @param array $record
     * @param string $expectedResult
     */
    public function testFormat(array $record, $expectedResult)
    {
        $formatter = new ConsoleFormatter();

        self::assertEquals($expectedResult, $formatter->format($record));
    }

    /**
     * @return array
     */
    public function recordsProvider()
    {
        return [
            'with context and extra' => [
                'record' => [
                    'datetime'   => new \DateTime('2018-07-06 09:16:02'),
                    'channel'    => 'app',
                    'level_name' => 'NOTICE',
                    'message'    => 'Message processed: {status}',
                    'level'      => 100,
                    'context'    => [
                        'status' => 'ACK'
                    ],
                    'extra'      => [
                        'processor'          => 'TestProcessor',
                        'message_body'       => 'message body',
                        'message_properties' => [
                            Config::PARAMETER_TOPIC_NAME => 'test topic'
                        ],
                        'message_id' => 1,
                        'elapsed_time' => '1 ms',
                        'time_taken'   => 1,
                        'memory_usage' => '1 MB',
                        'memory_taken' => '1 MB',
                        'peak_memory'  => '1 MB'
                    ]
                ],
                'expectedResult' => '2018-07-06 09:16:02 <fg=white>app.NOTICE</>: Message processed: <comment>ACK</> '
                    . '["status" => "ACK"] ["processor" => "TestProcessor","message_body" => "message body",'
                    . '"message_properties" => ["oro.message_queue.client.topic_name" => "test topic"],'
                    . '"message_id" => 1,"elapsed_time" => "1 ms","time_taken" => 1,'
                    . '"memory_usage" => "1 MB","memory_taken" => "1 MB","peak_memory" => "1 MB"]'
                    . "\n"
            ],
            'without context and with extra' => [
                'record' => [
                    'datetime'   => new \DateTime('2018-07-06 09:16:03'),
                    'channel'    => 'app',
                    'level_name' => 'INFO',
                    'message'    => 'Start consuming',
                    'level'      => 100,
                    'context'    => [],
                    'extra'      => [
                        'memory_usage' => '1 MB',
                    ]
                ],
                'expectedResult' => '2018-07-06 09:16:03 <fg=white>app.INFO</>: Start consuming '
                    . '["memory_usage" => "1 MB"]'
                    . "\n"
            ],
            'with context and without extra' => [
                'record' => [
                    'datetime'   => new \DateTime('2018-07-06 09:16:04'),
                    'channel'    => 'app',
                    'level_name' => 'NOTICE',
                    'message'    => 'Message processed: {status}',
                    'level'      => 100,
                    'context'    => [
                        'status' => 'ACK'
                    ],
                    'extra'      => []
                ],
                'expectedResult' => '2018-07-06 09:16:04 <fg=white>app.NOTICE</>: Message processed: <comment>ACK</> '
                    . '["status" => "ACK"]'
                    . "\n"
            ],
            'without context and without extra' => [
                'record' => [
                    'datetime'   => new \DateTime('2018-07-06 09:16:05'),
                    'channel'    => 'app',
                    'level_name' => 'INFO',
                    'message'    => 'Idle',
                    'level'      => 100,
                    'context'    => [],
                    'extra'      => []
                ],
                'expectedResult' => "2018-07-06 09:16:05 <fg=white>app.INFO</>: Idle\n"
            ],
        ];
    }
}
