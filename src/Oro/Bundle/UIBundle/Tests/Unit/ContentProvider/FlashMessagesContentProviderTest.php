<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\UIBundle\ContentProvider\FlashMessagesContentProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class FlashMessagesContentProviderTest extends TestCase
{
    private Session&MockObject $session;
    private RequestStack&MockObject $requestStack;
    private FlashMessagesContentProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);

        $this->provider = new FlashMessagesContentProvider($this->requestStack);
    }

    public function testGetContent(): void
    {
        $messages = ['test'];
        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('all')
            ->willReturn($messages);
        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->assertEquals($messages, $this->provider->getContent());
    }
}
