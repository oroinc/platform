<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\UIBundle\ContentProvider\FlashMessagesContentProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class FlashMessagesContentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var FlashMessagesContentProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);

        $this->provider = new FlashMessagesContentProvider($this->requestStack);
    }

    public function testGetContent()
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
