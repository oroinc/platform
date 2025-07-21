<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog\Handler;

use Oro\Bundle\LoggerBundle\Monolog\Handler\ExcludeLogByMessageHandler;
use PHPUnit\Framework\TestCase;

class ExcludeLogByMessageHandlerTest extends TestCase
{
    /**
     * @see ExcludeLogByMessageHandler::isHandling()
     */
    public function testIsHandling(): void
    {
        $emptyIsHandling = $this->getExcludeLogByMessageHandler([])->isHandling([]);
        $excludeLogMessage = ['message' => 'Failed to connect to websocket server'];
        $excludeLogsIsHandling = $this->getExcludeLogByMessageHandler($excludeLogMessage)
            ->isHandling($excludeLogMessage);

        self::assertTrue($emptyIsHandling);
        self::assertTrue($excludeLogsIsHandling);
    }

    /**
     * @dataProvider handleDataProvider
     * @see ExcludeLogByMessageHandler::handle()
     */
    public function testHandle(array $excludeMessages, array $record, bool $result): void
    {
        $handler = $this->getExcludeLogByMessageHandler($excludeMessages);

        self::assertSame($result, $handler->handle($record));
    }

    public function handleDataProvider(): array
    {
        return [
            'empty exclude messages' => [
                'excludeMessages' => [],
                'record' => [
                    'message' => 'Failed to connect to websocket server'
                ],
                'result' => false
            ],
            'exclude message not match' => [
                'excludeMessages' => [
                    'Error occurred while rendering content widget'
                ],
                'record' => [
                    'message' => 'Failed to connect to websocket server'
                ],
                'result' => false
            ],
            'exclude message matched' => [
                'excludeMessages' => [
                    'Failed to connect to websocket server'
                ],
                'record' => [
                    'message' => 'Failed to connect to websocket server: error trace'
                ],
                'result' => true
            ],
            'exclude message matched with Exception' => [
                'excludeMessages' => [
                    'Cannot load the cache state date from the database'
                ],
                'record' => [
                    'context' => [
                        'exception' => new \LogicException(
                            'Cannot load the cache state date from the database: trace'
                        )
                    ]
                ],
                'result' => true
            ],
            'exclude message not matched with Exception' => [
                'excludeMessages' => [
                    'Failed to connect to websocket server'
                ],
                'record' => [
                    'context' => [
                        'exception' => new \LogicException(
                            'Cannot load the cache state date from the database: trace'
                        )
                    ]
                ],
                'result' => false
            ]
        ];
    }

    /**
     * @see ExcludeLogByMessageHandler::handleBatch()
     */
    public function testHandleBatch(): void
    {
        $records = [
            ['message' => 'Failed to connect to websocket server'],
            ['message' => 'Cannot load the cache state date from the database'],
            ['message' => 'Error occurred while rendering content widget'],
        ];
        $mockedHandler = $this->getMockBuilder(ExcludeLogByMessageHandler::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['handleBatch', 'handle'])
            ->getMock();
        $mockedHandler->expects($this->once())
            ->method('handleBatch')
            ->with($records);
        $mockedHandler->expects($this->any())
            ->method('handle')
            ->withConsecutive(...$records);

        $mockedHandler->handleBatch($records);
    }

    protected function getExcludeLogByMessageHandler(array $excludeMessages): ExcludeLogByMessageHandler
    {
        return new ExcludeLogByMessageHandler($excludeMessages);
    }
}
