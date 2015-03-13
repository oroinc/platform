<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Error\ErrorHandler;

class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ErrorHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->handler = new ErrorHandler();
        $this->handler->registerHandlers();
    }

    protected function tearDown()
    {
        unset($this->handler);
        restore_error_handler();
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
    public function testHandleError()
    {
        trigger_error('Test error', E_USER_ERROR);
    }

    public function testHandleIgnoredErrorIfErrorsSuppressed()
    {
        @$this->handler->handle(E_ERROR, 'test', '', 0);
    }

    public function testHandleIgnoreWarnings()
    {
        $this->assertFalse($this->handler->handle(E_WARNING, 'Test warning', '', 0));
    }

    public function warningDataProvider()
    {
        return array(
            'silenced php_network_getaddresses' => array(
                'message' => 'PDO::__construct(): php_network_getaddresses: getaddrinfo failed: No such host is known'
            ),
            'passed warning' => array(
                'message' => 'Some regualar warning',
                'silenced' => false,
            )
        );
    }
}
