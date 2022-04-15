<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\UserLoginAttemptLogger;
use Oro\Bundle\WsseAuthenticationBundle\EventListener\AuthenticationListener;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserLoginAttemptLogger|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var AuthenticationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(UserLoginAttemptLogger::class);
        $this->listener = new AuthenticationListener($this->logger);
    }

    public function testOnAuthenticationSuccessWithNotSupportedToken(): void
    {
        $token = new UsernamePasswordToken('test', 'main');
        $event = new AuthenticationEvent($token);

        $this->logger->expects(self::never())
            ->method('logSuccessLoginAttempt');

        $this->listener->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessWithNotUserInToken(): void
    {
        $token = new WsseToken($this->createMock(UserInterface::class), 'main', 'test');
        $event = new AuthenticationEvent($token);

        $this->logger->expects(self::never())
            ->method('logSuccessLoginAttempt');

        $this->listener->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $user = new User();
        $token = new WsseToken($user, 'main', 'test');
        $event = new AuthenticationEvent($token);

        $this->logger->expects(self::once())
            ->method('logSuccessLoginAttempt')
            ->with($user, 'wsse');

        $this->listener->onAuthenticationSuccess($event);
    }
}
