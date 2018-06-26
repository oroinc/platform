<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Error\ErrorHandler;

class ErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ErrorHandler
     */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->handler = new ErrorHandler();
        $this->handler->registerHandlers();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->handler);
        restore_error_handler();
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * @param string $message
     * @param bool $silenced
     * @dataProvider warningDataProvider
     */
    public function testHandleWarning($message, $silenced = true)
    {
        $this->assertEquals($silenced, $this->handler->handleWarning(E_WARNING, $message));
    }

    /**
     * @expectedException \ErrorException
     * @expectedExceptionMessage Test error
     */
    public function testHandleErrors()
    {
        trigger_error('Test error', E_USER_ERROR);
    }

    public function testHandleIgnoredErrorsIfErrorsSuppressed()
    {
        @$this->handler->handleErrors(E_ERROR, 'test', '', 0);
    }

    public function testHandleIgnoreWarnings()
    {
        $this->assertFalse($this->handler->handleErrors(E_WARNING, 'Test warning', '', 0));
    }

    /**
     * @return array
     */
    public function warningDataProvider()
    {
        return [
            'silenced php_network_getaddresses' => [
                'message' => 'PDO::__construct(): php_network_getaddresses: getaddrinfo failed: No such host is known'
            ],
            'passed warning' => [
                'message' => 'Some regualar warning',
                'silenced' => false,
            ]
        ];
    }
}
