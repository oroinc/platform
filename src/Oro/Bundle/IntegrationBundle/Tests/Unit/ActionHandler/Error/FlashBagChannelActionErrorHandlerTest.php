<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler\Error;

use Oro\Bundle\IntegrationBundle\ActionHandler\Error\FlashBagChannelActionErrorHandler;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

class FlashBagChannelActionErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $session;

    /**
     * @var FlashBagChannelActionErrorHandler
     */
    private $errorHandler;

    protected function setUp()
    {
        $this->session = $this->createMock(Session::class);

        $this->errorHandler = new FlashBagChannelActionErrorHandler($this->session);
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
