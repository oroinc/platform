<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler\Error;

use Oro\Bundle\IntegrationBundle\ActionHandler\Error\FlashBagChannelActionErrorHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

class FlashBagChannelActionErrorHandlerTest extends TestCase
{
    private Session&MockObject $session;
    private RequestStack&MockObject $requestStack;
    private FlashBagChannelActionErrorHandler $errorHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);

        $this->errorHandler = new FlashBagChannelActionErrorHandler($this->requestStack);
    }

    public function testHandleErrors(): void
    {
        $errors = ['error1', 'error2'];

        $flashBag = new FlashBag();
        $this->session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->errorHandler->handleErrors($errors);

        self::assertSame($errors, $flashBag->get('error'));
    }
}
