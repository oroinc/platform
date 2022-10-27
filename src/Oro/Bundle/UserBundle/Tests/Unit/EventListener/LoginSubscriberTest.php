<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\EventListener\LoginSubscriber;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\LoginInfoInterfaceStub;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\PasswordRecoveryInterfaceStub;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var BaseUserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var LoginSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(BaseUserManager::class);

        $this->subscriber = new LoginSubscriber($this->userManager);
    }

    public function testOnLogin(): void
    {
        $user = new User();
        $user->setId(42)
            ->setConfirmationToken('confirmation_token')
            ->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $this->assertNull($user->getLastLogin());
        $this->assertNull($user->getLoginCount());
        $this->assertNotNull($user->getConfirmationToken());
        $this->assertNotNull($user->getPasswordRequestedAt());

        $this->userManager->expects($this->exactly(2))
            ->method('updateUser')
            ->with($user);

        $this->subscriber->onLogin(
            new InteractiveLoginEvent(new Request(), new UsernamePasswordToken($user, 'user', 'key'))
        );

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify('-1 minute');

        $this->assertGreaterThan($date, $user->getLastLogin());
        $this->assertEquals(1, $user->getLoginCount());
        $this->assertNull($user->getConfirmationToken());
        $this->assertNull($user->getPasswordRequestedAt());

        $this->subscriber->onLogin(
            new InteractiveLoginEvent(new Request(), new UsernamePasswordToken($user, 'user', 'key'))
        );

        $this->assertGreaterThan($date, $user->getLastLogin());
        $this->assertEquals(2, $user->getLoginCount());
        $this->assertNull($user->getConfirmationToken());
        $this->assertNull($user->getPasswordRequestedAt());
    }

    public function testOnLoginWithLoginInfoInterface(): void
    {
        $user = new LoginInfoInterfaceStub();

        $this->assertNull($user->getLastLogin());
        $this->assertNull($user->getLoginCount());

        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($user);

        $this->subscriber->onLogin(
            new InteractiveLoginEvent(new Request(), new UsernamePasswordToken($user, 'user', 'key'))
        );

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify('-1 minute');

        $this->assertGreaterThan($date, $user->getLastLogin());
        $this->assertEquals(1, $user->getLoginCount());
    }

    public function testOnLoginWithPasswordRecoveryInterface(): void
    {
        $user = new PasswordRecoveryInterfaceStub();
        $user->setConfirmationToken('confirmation_token')
            ->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $this->assertNotNull($user->getConfirmationToken());
        $this->assertNotNull($user->getPasswordRequestedAt());

        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($user);

        $this->subscriber->onLogin(
            new InteractiveLoginEvent(new Request(), new UsernamePasswordToken($user, 'user', 'key'))
        );

        $this->assertNull($user->getConfirmationToken());
        $this->assertNull($user->getPasswordRequestedAt());
    }

    public function testOnLoginWithPasswordRecoveryInterfaceAndEmptyConfirmationToken(): void
    {
        $user = new PasswordRecoveryInterfaceStub();
        $user->setConfirmationToken(null)
            ->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $this->assertNull($user->getConfirmationToken());
        $this->assertNotNull($user->getPasswordRequestedAt());

        $this->userManager->expects($this->never())
            ->method('updateUser');

        $this->subscriber->onLogin(
            new InteractiveLoginEvent(new Request(), new UsernamePasswordToken($user, 'user', 'key'))
        );

        $this->assertNull($user->getConfirmationToken());
        $this->assertNotNull($user->getPasswordRequestedAt());
    }

    public function testOnLoginWithUserInterface(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->userManager->expects($this->never())
            ->method('updateUser');

        $this->subscriber->onLogin(
            new InteractiveLoginEvent(new Request(), new UsernamePasswordToken($user, 'user', 'key'))
        );
    }
}
