<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog\Handler;

use Monolog\Level;
use Monolog\LogRecord;
use Oro\Bundle\LoggerBundle\Monolog\Handler\ExcludeLogByMessageHandler;
use PHPUnit\Framework\TestCase;

class ExcludeLogByMessageHandlerTest extends TestCase
{
    /**
     * @see ExcludeLogByMessageHandler::isHandling()
     */
    public function testIsHandling(): void
    {
        $emptyIsHandling = $this->getExcludeLogByMessageHandler([])->isHandling(
            new LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'test',
                level: Level::Error,
                message: 'test'
            )
        );
        $excludeLogMessage = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'Failed to connect to websocket server'
        );
        $excludeLogsIsHandling = $this->getExcludeLogByMessageHandler(['Failed to connect to websocket server'])
            ->isHandling($excludeLogMessage);

        self::assertTrue($emptyIsHandling);
        self::assertTrue($excludeLogsIsHandling);
    }

    /**
     * @dataProvider handleDataProvider
     * @see ExcludeLogByMessageHandler::handle()
     */
    public function testHandle(array $excludeMessages, LogRecord $record, bool $result): void
    {
        $handler = $this->getExcludeLogByMessageHandler($excludeMessages);

        self::assertSame($result, $handler->handle($record));
    }

    public function handleDataProvider(): array
    {
        return [
            'empty exclude messages' => [
                'excludeMessages' => [],
                'record' => new LogRecord(
                    datetime: new \DateTimeImmutable(),
                    channel: 'test',
                    level: Level::Error,
                    message: 'Failed to connect to websocket server'
                ),
                'result' => false
            ],
            'exclude message not match' => [
                'excludeMessages' => [
                    'Error occurred while rendering content widget'
                ],
                'record' => new LogRecord(
                    datetime: new \DateTimeImmutable(),
                    channel: 'test',
                    level: Level::Error,
                    message: 'Failed to connect to websocket server'
                ),
                'result' => false
            ],
            'exclude message matched' => [
                'excludeMessages' => [
                    'Failed to connect to websocket server'
                ],
                'record' => new LogRecord(
                    datetime: new \DateTimeImmutable(),
                    channel: 'test',
                    level: Level::Error,
                    message: 'Failed to connect to websocket server: error trace'
                ),
                'result' => true
            ],
            'exclude message matched with Exception' => [
                'excludeMessages' => [
                    'Cannot load the cache state date from the database'
                ],
                'record' => new LogRecord(
                    datetime: new \DateTimeImmutable(),
                    channel: 'test',
                    level: Level::Error,
                    message: '',
                    context: [
                        'exception' => new \LogicException(
                            'Cannot load the cache state date from the database: trace'
                        )
                    ]
                ),
                'result' => true
            ],
            'exclude message not matched with Exception' => [
                'excludeMessages' => [
                    'Failed to connect to websocket server'
                ],
                'record' => new LogRecord(
                    datetime: new \DateTimeImmutable(),
                    channel: 'test',
                    level: Level::Error,
                    message: '',
                    context: [
                        'exception' => new \LogicException(
                            'Cannot load the cache state date from the database: trace'
                        )
                    ]
                ),
                'result' => false
            ]
        ];
    }

    /**
     * @see ExcludeLogByMessageHandler::handleBatch()
     */
    public function testHandleBatch(): void
    {
        $record1 = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'Failed to connect to websocket server'
        );
        $record2 = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'Cannot load the cache state date from the database'
        );
        $record3 = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'Error occurred while rendering content widget'
        );
        $records = [$record1, $record2, $record3];

        $mockedHandler = $this->getMockBuilder(ExcludeLogByMessageHandler::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['handleBatch', 'handle'])
            ->getMock();
        $mockedHandler->expects($this->once())
            ->method('handleBatch')
            ->with($records);
        $mockedHandler->expects($this->any())
            ->method('handle')
            ->withConsecutive([$record1], [$record2], [$record3]);

        $mockedHandler->handleBatch($records);
    }

    protected function getExcludeLogByMessageHandler(array $excludeMessages): ExcludeLogByMessageHandler
    {
        return new ExcludeLogByMessageHandler($excludeMessages);
    }
}
