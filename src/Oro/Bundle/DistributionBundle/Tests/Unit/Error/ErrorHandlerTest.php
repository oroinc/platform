<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Error;

use Oro\Bundle\DistributionBundle\Error\ErrorHandler;
use Psr\Log\LoggerInterface;

class ErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ErrorHandler
     */
    private $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->handler = ErrorHandler::register();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->handler);
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * @throws \ErrorException
     */
    public function testSilenceGetaddresses(): void
    {
        $this->assertTrue(
            $this->handler->handleError(
                E_WARNING,
                'PDO::__construct(): php_network_getaddresses: getaddrinfo failed: No such host is known',
                '',
                0
            )
        );

        trigger_error(
            'PDO::__construct(): php_network_getaddresses: getaddrinfo failed: No such host is known',
            E_USER_WARNING
        );
    }

    /**
     * @dataProvider silinceDataProvider
     */
    public function testSilenceReflectionToString(string $message): void
    {
        if (PHP_VERSION_ID < 70400) {
            $this->markTestSkipped('Only for PHP >= 7.4');
        }

        $this->assertTrue($this->handler->handleError(E_DEPRECATED, $message, '', 0));

        // This error must not logged
        trigger_error($message, E_USER_DEPRECATED);
    }

    /**
     * @dataProvider silinceDataProvider
     */
    public function testNotSilenceReflectionToString(string $message): void
    {
        if (PHP_VERSION_ID >= 70400) {
            $this->markTestSkipped('Only for PHP < 7.4');
        }

        $this->assertFalse($this->handler->handleError(E_DEPRECATED, $message, '', 0));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('info', 'User Deprecated: ' . $message, $this->isType('array'));

        $this->handler->setLoggers([
            E_USER_DEPRECATED => [$logger]
        ]);

        trigger_error($message, E_USER_DEPRECATED);
    }

    public function silinceDataProvider(): array
    {
        return [
            ['Function ReflectionType::__toString() is deprecated'],
            ['Unparenthesized `a ? b : c ? d : e` is deprecated`'],
        ];
    }

    public function testHandleErrors()
    {
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Test error');

        trigger_error('Test error', E_USER_ERROR);
    }
}
