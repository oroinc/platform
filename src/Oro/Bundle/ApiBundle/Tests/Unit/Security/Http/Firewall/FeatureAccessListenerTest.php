<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Security\Http\Firewall;

use Oro\Bundle\ApiBundle\Security\Http\Firewall\FeatureAccessListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FeatureAccessListenerTest extends TestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private FeatureAccessListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->listener = new FeatureAccessListener($this->tokenStorage);
    }

    public function testHandleWhenTokenExists(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));

        ($this->listener)($this->createMock(RequestEvent::class));
    }

    public function testHandleWhenTokenDoesNotExist(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        ($this->listener)($this->createMock(RequestEvent::class));
    }
}
