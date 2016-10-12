<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\EventListener\LoginHistorySubscriber;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;
use Oro\Bundle\UserBundle\Security\LoginHistoryManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginHistorySubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesNotDisableUserOnRemainingAttempts()
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setUsername('john');
        $subscriber = $this->getSubscriber(3, 1, $user);
        $event = $this->getEvent($user->getUsername());

        $subscriber->onAuthenticationFailure($event);

        $this->assertTrue($user->isEnabled());
    }

    public function testDisableUser()
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setUsername('john');
        $subscriber = $this->getSubscriber(0, 1, $user);
        $event = $this->getEvent($user->getUsername());

        $subscriber->onAuthenticationFailure($event);

        $this->assertFalse($user->isEnabled());
    }

    public function testDoesNotLogEmptyUsernames()
    {
        $subscriber = $this->getSubscriber(0, 0, null);
        $event = $this->getEvent('john');

        $subscriber->onAuthenticationFailure($event);
    }

    /**
     * @param int $nbUserLogins
     * @return LoginHistoryManager
     */
    private function getLoginHistoryManager($nbUserLogins)
    {
        $provider = $this->getMockBuilder(LoginHistoryManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->exactly($nbUserLogins))
            ->method('logUserLogin');

        return $provider;
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
     * @param int $nbUserLogins
     * @param UserInterface|null $user
     * @return LoginHistorySubscriber
     */
    private function getSubscriber($remainingAttempts, $nbUserLogins, UserInterface $user = null)
    {
        return new LoginHistorySubscriber(
            $this->getLoginHistoryManager($nbUserLogins),
            $this->getLoginAttemptsProvider($remainingAttempts),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );
    }
}
