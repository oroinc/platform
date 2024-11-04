<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler\Error;

use Oro\Bundle\IntegrationBundle\ActionHandler\Error\FlashBagChannelActionErrorHandler;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

class FlashBagChannelActionErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var FlashBagChannelActionErrorHandler */
    private $errorHandler;

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

    public function testHandleErrors()
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
