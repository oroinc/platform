<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\EventListener\LoginAttemptsSubscriber;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Manager\LoginAttemptsManager;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginAttemptsSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesNotTrackUnknownUsernames()
    {
        $subscriber = $this->getSubscriber(null);
        $event = $this->getFailureEvent('john');

        $subscriber->onAuthenticationFailure($event);
    }

    public function testDoesNotTrackUsersWithoutFailedLoginInfoOnFailure()
    {
        $subscriber = $this->getSubscriber(new \stdClass());
        $event = $this->getFailureEvent('john');

        $subscriber->onAuthenticationFailure($event);
    }

    public function testDoesNotTrackUsersWithoutFailedLoginInfoOnSuccess()
    {
        $subscriber = $this->getSubscriber();
        $event = $this->getInteractiveLoginEvent(new \stdClass());

        $subscriber->onInteractiveLogin($event);
    }

    public function shouldTrackFailures()
    {
        $subscriber = $this->getSubscriber(new User(), 1);
        $event = $this->getFailureEvent('john');

        $subscriber->onAuthenticationFailure($event);
    }

    public function testShouldTrackInteractiveLogins()
    {
        $subscriber = $this->getSubscriber(new User(), 0, 1);
        $event = $this->getInteractiveLoginEvent(new User());

        $subscriber->onInteractiveLogin($event);
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
     * @param object|null $user
     * @return BaseUserManager
     */
    private function getUserManager($user = null)
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
     * @param int $failCalls
     * @param int $successCalls
     * @return LoginAttemptsManager
     */
    private function getAttemptsManager($failCalls = 0, $successCalls = 0)
    {
        $manager = $this->getMockBuilder(LoginAttemptsManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->exactly($failCalls))
            ->method('trackLoginFailure');

        $manager->expects($this->exactly($successCalls))
            ->method('trackLoginSuccess');

        return $manager;
    }

    /**
     * @param string $username
     * @return AuthenticationFailureEvent
     */
    private function getFailureEvent($username)
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
     * @param object $user
     * @return InteractiveLoginEvent
     */
    private function getInteractiveLoginEvent($user)
    {
        $token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $event = $this->getMockBuilder(InteractiveLoginEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getAuthenticationToken')
            ->willReturn($token);

        return $event;
    }

    /**
     * @param object|null $user
     * @param int $failCalls
     * @param int $successCalls
     * @return LoginAttemptsSubscriber
     */
    private function getSubscriber($user = null, $failCalls = 0, $successCalls = 0)
    {
        return new LoginAttemptsSubscriber(
            $this->getUserManager($user),
            $this->getAttemptsManager($failCalls, $successCalls)
        );
    }
}
