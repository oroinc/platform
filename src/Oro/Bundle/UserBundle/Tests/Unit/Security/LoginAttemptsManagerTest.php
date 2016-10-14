<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\LoginAttemptsManager;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;

class LoginAttemptsManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesNotDisableUserOnRemainingAttempts()
    {
        $user = $this->getUser();
        $manager = $this->getLoginManager(3, $user);

        $manager->trackLoginFailure($user);

        $this->assertTrue($user->isEnabled());
    }

    public function testIncrementCountersOnFailedLogin()
    {
        $user = $this->getUser();
        $manager = $this->getLoginManager(3, $user);

        $manager->trackLoginFailure($user);

        $this->assertSame(1, $user->getDailyFailedLoginCount());
        $this->assertSame(1, $user->getFailedLoginCount());
    }

    public function testDisableUserOnExeededLimits()
    {
        $user = $this->getUser();
        $manager = $this->getLoginManager(0, $user);

        $manager->trackLoginFailure($user);

        $this->assertFalse($user->isEnabled());
    }

    public function testResetCountersOnSuccessfulLogin()
    {
        $user = $this->getUser(8, 5);
        $manager = $this->getLoginManager(3, $user);

        $manager->trackLoginSuccess($user);

        $this->assertSame(0, $user->getDailyFailedLoginCount());
        $this->assertSame(0, $user->getFailedLoginCount());
    }

    public function testResetDailyCounterOnFirstFailedLoginToday()
    {
        $lastFail = new \DateTime('now', new \DateTimeZone('UTC'));
        $lastFail->modify('-1 day');

        $user = $this->getUser(8, 25, $lastFail);
        $manager = $this->getLoginManager(3, $user);

        $manager->trackLoginFailure($user);

        $this->assertSame(1, $user->getDailyFailedLoginCount());
    }

    /**
     * @param int $dailyLoginFails
     * @param int $loginFails
     * @param \DateTime $lastFailedLogin
     * @return User
     */
    private function getUser($dailyLoginFails = 0, $loginFails = 0, \DateTime $lastFailedLogin = null)
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setUsername('john');
        $user->setDailyFailedLoginCount($dailyLoginFails);
        $user->setFailedLoginCount($loginFails);
        $user->setLastFailedLogin($lastFailedLogin);

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
     * @param User|null $user
     * @return BaseUserManager
     */
    private function getUserManager(User $user = null)
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
     * @param int $remainingAttempts
     * @param User|null $user
     * @return LoginAttemptsManager
     */
    private function getLoginManager($remainingAttempts, User $user = null)
    {
        return new LoginAttemptsManager(
            $this->getLoginAttemptsProvider($remainingAttempts),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );
    }
}
