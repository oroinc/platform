<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Error;

use Oro\Bundle\DistributionBundle\Error\ErrorHandler;
use Symfony\Component\ErrorHandler\ErrorHandler as SymfonyErrorHandler;

class ErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    private SymfonyErrorHandler $handler;

    protected function setUp(): void
    {
        $this->handler = ErrorHandler::register();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function testSilenceGetAddresses(): void
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
     * @dataProvider silenceDataProvider
     */
    public function testSilenceReflectionToString(string $message): void
    {
        $this->assertTrue($this->handler->handleError(E_DEPRECATED, $message, '', 0));

        // This error must not logged
        trigger_error($message, E_USER_DEPRECATED);
    }

    public function silenceDataProvider(): array
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
