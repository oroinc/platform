<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler\Error;

use Oro\Bundle\IntegrationBundle\ActionHandler\Error\ChannelActionErrorHandler;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

class ChannelActionErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * @var ChannelActionErrorHandler
     */
    private $errorHandler;

    protected function setUp()
    {
        $this->session = $this->createMock(Session::class);

        $this->errorHandler = new ChannelActionErrorHandler($this->session);
    }

    public function testHandleErrors()
    {
        $errors = ['error1', 'error2'];

        $flashBag = new FlashBag();
        $this->session->expects(static::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->errorHandler->handleErrors($errors);

        static::assertSame($errors, $flashBag->get('error'));
    }
}
