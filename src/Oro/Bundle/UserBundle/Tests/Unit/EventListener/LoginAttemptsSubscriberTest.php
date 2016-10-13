<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\EventListener\LoginAttemptsSubscriber;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;

class LoginAttemptsSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesNotDisableUserOnRemainingAttempts()
    {
        $user = $this->getUser();
        $subscriber = $this->getSubscriber(3, $user);
        $event = $this->getEvent($user->getUsername());

        $subscriber->onAuthenticationFailure($event);

        $this->assertTrue($user->isEnabled());
    }

    public function testIncrementCountersOnFailedLogin()
    {
        $user = $this->getUser();
        $subscriber = $this->getSubscriber(3, $user);
        $event = $this->getEvent($user->getUsername());

        $subscriber->onAuthenticationFailure($event);

        $this->assertSame(1, $user->getDailyFailedLoginCount());
        $this->assertSame(1, $user->getFailedLoginCount());
    }

    public function testDisableUser()
    {
        $user = $this->getUser();
        $subscriber = $this->getSubscriber(0, $user);
        $event = $this->getEvent($user->getUsername());

        $subscriber->onAuthenticationFailure($event);

        $this->assertFalse($user->isEnabled());
    }

    public function testDoesNotLogEmptyUsernames()
    {
        $subscriber = $this->getSubscriber(0, null);
        $event = $this->getEvent('john');

        $subscriber->onAuthenticationFailure($event);
    }

    /**
     * @return User
     */
    private function getUser()
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setUsername('john');

        return $user;
    }

    /**
     * @param int $remainingAttempts
     * @return LoginAttemptsProvider
     */
    private function getLoginAttemptsProvider($remainingAttempts)
    {
        $provider = $this->getMockBuilder(LoginAttemptsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $provider->expects($this->any())
            ->method('getByUser')
            ->willReturn($remainingAttempts);

        $provider->expects($this->any())
            ->method('hasRemainingAttempts')
            ->willReturn(0 !== $remainingAttempts);

        return $provider;
    }

    /**
     * @param UserInterface|null $user
     * @return BaseUserManager
     */
    private function getUserManager(UserInterface $user = null)
    {
        $manager = $this->getMockBuilder(BaseUserManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('findUserByUsernameOrEmail')
            ->willReturn($user);

        return $manager;
    }

    /**
     * @return Processor
     */
    private function getMailProcessor()
    {
        return $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $username
     * @return AuthenticationFailureEvent
     */
    private function getEvent($username)
    {
        $token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($username);

        $event = $this->getMockBuilder(AuthenticationFailureEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getAuthenticationToken')
            ->willReturn($token);

        return $event;
    }

    /**
     * @param int $remainingAttempts
     * @param UserInterface|null $user
     * @return LoginAttemptsSubscriber
     */
    private function getSubscriber($remainingAttempts, UserInterface $user = null)
    {
        return new LoginAttemptsSubscriber(
            $this->getLoginAttemptsProvider($remainingAttempts),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );
    }
}
