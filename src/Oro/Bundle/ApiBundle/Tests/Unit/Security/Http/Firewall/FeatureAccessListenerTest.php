<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Security\Http\Firewall;

use Oro\Bundle\ApiBundle\Security\Http\Firewall\FeatureAccessListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FeatureAccessListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var FeatureAccessListener */
    private $listener;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->listener = new FeatureAccessListener($this->tokenStorage);
    }

    public function testHandleWhenTokenExists()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));

        $this->listener->handle($this->createMock(GetResponseEvent::class));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testHandleWhenTokenDoesNotExist()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->listener->handle($this->createMock(GetResponseEvent::class));
    }
}
