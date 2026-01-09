<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Formatter;

use Monolog\Level;
use Monolog\LogRecord;
use Oro\Bundle\MessageQueueBundle\Log\Formatter\ConsoleFormatter;
use Oro\Component\MessageQueue\Client\Config;
use PHPUnit\Framework\TestCase;

class ConsoleFormatterTest extends TestCase
{
    /**
     * @dataProvider recordsProvider
     */
    public function testFormat(LogRecord $record, string $expectedResult): void
    {
        $formatter = new ConsoleFormatter();

        self::assertEquals($expectedResult, $formatter->format($record));
    }

    public function recordsProvider(): array
    {
        return [
            'with context and extra' => [
                'record' => new LogRecord(
                    datetime: new \DateTimeImmutable('2018-07-06 09:16:02'),
                    channel: 'app',
                    level: Level::Notice,
                    message: 'Message processed: {status}',
                    context: [
                        'status' => 'ACK'
                    ],
                    extra: [
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
                ),
                'expectedResult' => '2018-07-06 09:16:02 <fg=blue>app.NOTICE</>: Message processed: <comment>ACK</> '
                    . '["status" => "ACK"] ["processor" => "TestProcessor","message_body" => "message body",'
                    . '"message_properties" => ["oro.message_queue.client.topic_name" => "test topic"],'
                    . '"message_id" => 1,"elapsed_time" => "1 ms","time_taken" => 1,'
                    . '"memory_usage" => "1 MB","memory_taken" => "1 MB","peak_memory" => "1 MB"]'
                    . "\n"
            ],
            'without context and with extra' => [
                'record' => new LogRecord(
                    datetime: new \DateTimeImmutable('2018-07-06 09:16:03'),
                    channel: 'app',
                    level: Level::Info,
                    message: 'Start consuming',
                    context: [],
                    extra: [
                        'memory_usage' => '1 MB',
                    ]
                ),
                'expectedResult' => '2018-07-06 09:16:03 <fg=green>app.INFO</>: Start consuming '
                    . '["memory_usage" => "1 MB"]'
                    . "\n"
            ],
            'with context and without extra' => [
                'record' => new LogRecord(
                    datetime: new \DateTimeImmutable('2018-07-06 09:16:04'),
                    channel: 'app',
                    level: Level::Notice,
                    message: 'Message processed: {status}',
                    context: [
                        'status' => 'ACK'
                    ],
                    extra: []
                ),
                'expectedResult' => '2018-07-06 09:16:04 <fg=blue>app.NOTICE</>: Message processed: <comment>ACK</> '
                    . '["status" => "ACK"]'
                    . "\n"
            ],
            'without context and without extra' => [
                'record' => new LogRecord(
                    datetime: new \DateTimeImmutable('2018-07-06 09:16:05'),
                    channel: 'app',
                    level: Level::Info,
                    message: 'Idle',
                    context: [],
                    extra: []
                ),
                'expectedResult' => "2018-07-06 09:16:05 <fg=green>app.INFO</>: Idle\n"
            ],
        ];
    }
}
