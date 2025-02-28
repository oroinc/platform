<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Oro\Bundle\LoggerBundle\Monolog\DisableDeprecationsHandlerWrapper;
use Oro\Bundle\LoggerBundle\Monolog\LogLevelConfig;
use Oro\Bundle\LoggerBundle\Test\MonologTestCaseTrait;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

class DisableDeprecationsHandlerWrapperTest extends TestCase
{
    use MonologTestCaseTrait;

    private LogLevelConfig|\PHPUnit\Framework\MockObject\MockObject $config;
    private DisableDeprecationsHandlerWrapper $wrapper;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(HandlerInterface::class);
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(
        InvokedCount $expectsCall,
        int $severity,
        int $logLevel,
        string $message
    ): void {
        $this->wrapper = new DisableDeprecationsHandlerWrapper($this->inner);
        $exception = new \ErrorException(severity: $severity);
        $record = $this->getLogRecord($logLevel, $message, ['exception' => $exception]);
        $this->inner->expects($expectsCall)
            ->method('handle')
            ->with($record);

        $this->wrapper->handle($record);
    }

    public function handleDataProvider(): array
    {
        return [
            'collect deprecations E_USER_DEPRECATED' => [
                'expectsCalls' => self::never(),
                'severity' => \E_USER_DEPRECATED,
                'logLevel' => Logger::INFO,
                'message' => 'Deprecated Message',
            ],
            'collect deprecations E_DEPRECATED' => [
                'expectsCalls' => self::never(),
                'severity' => \E_DEPRECATED,
                'logLevel' => Logger::INFO,
                'message' => 'Deprecated Message',
            ],
            'exception severity is not match' => [
                'expectsCalls' => self::once(),
                'severity' => \E_ERROR,
                'logLevel' => Logger::ERROR,
                'message' => 'Error Message',
            ]
        ];
    }
}
