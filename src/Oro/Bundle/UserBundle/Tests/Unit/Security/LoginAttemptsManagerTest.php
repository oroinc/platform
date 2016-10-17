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
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(3, 3),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );

        $manager->trackLoginFailure($user);

        $this->assertTrue($user->isEnabled());
    }

    public function testIncrementOnlyCumulativeCounterOnFailedLogin()
    {
        $user = $this->getUser(5, 5);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(0, 10),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );

        $manager->trackLoginFailure($user);

        $this->assertSame(5, $user->getDailyFailedLoginCount());
        $this->assertSame(6, $user->getFailedLoginCount());
    }

    public function testIncrementOnlyDailyCounterOnFailedLogin()
    {
        $user = $this->getUser(5, 5);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(10, 0),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );

        $manager->trackLoginFailure($user);

        $this->assertSame(6, $user->getDailyFailedLoginCount());
        $this->assertSame(5, $user->getFailedLoginCount());
    }

    public function testDeactivateUserOnExeededLimit()
    {
        $user = $this->getUser(3, 17);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(5, 10),
            $this->getUserManager($user),
            $this->getMailProcessor(0, 1)
        );

        $manager->trackLoginFailure($user);

        $this->assertFalse($user->isEnabled());
    }

    public function testDeactivateUserOnExeededDailyLimit()
    {
        $user = $this->getUser(5, 10);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(3, 17),
            $this->getUserManager($user),
            $this->getMailProcessor(1, 0)
        );

        $manager->trackLoginFailure($user);

        $this->assertFalse($user->isEnabled());
    }

    public function testResetOnlyCumulativeCounterOnSuccessfulLogin()
    {
        $user = $this->getUser(8, 5);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(0, 30),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );

        $manager->trackLoginSuccess($user);

        $this->assertSame(8, $user->getDailyFailedLoginCount());
        $this->assertSame(0, $user->getFailedLoginCount());
    }

    public function testResetOnlyDailyCounterOnSuccessfulLogin()
    {
        $user = $this->getUser(8, 5);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(30, 0),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );

        $manager->trackLoginSuccess($user);

        $this->assertSame(0, $user->getDailyFailedLoginCount());
        $this->assertSame(5, $user->getFailedLoginCount());
    }

    public function testResetDailyCounterOnFirstFailedLoginToday()
    {
        $lastFail = new \DateTime('now', new \DateTimeZone('UTC'));
        $lastFail->modify('-1 day');

        $user = $this->getUser(8, 25, $lastFail);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(30, 0),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );

        $manager->trackLoginFailure($user);

        $this->assertSame(1, $user->getDailyFailedLoginCount());
        $this->assertSame(25, $user->getFailedLoginCount());
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
     * @param int $dailyLimit
     * @param int $limit
     * @return LoginAttemptsProvider
     */
    private function getLoginAttemptsProvider(
        $dailyLimit = 0,
        $limit = 0
    ) {
        $provider = $this->getMockBuilder(LoginAttemptsProvider::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getMaxDailyLoginAttempts',
                'hasDailyLimit',
                'getMaxCumulativeLoginAttempts',
                'hasCumulativeLimit'
            ])
            ->getMock();

        $provider->expects($this->any())
            ->method('getMaxDailyLoginAttempts')
            ->willReturn($dailyLimit);

        $provider->expects($this->any())
            ->method('hasDailyLimit')
            ->willReturn(0 !== $dailyLimit);

        $provider->expects($this->any())
            ->method('getMaxCumulativeLoginAttempts')
            ->willReturn($limit);

        $provider->expects($this->any())
            ->method('hasCumulativeLimit')
            ->willReturn(0 !== $limit);

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
     * @param  int $nbDailyEmails
     * @param  int $nbCumulativeEmails
     * @return Processor
     */
    private function getMailProcessor($nbDailyEmails = 0, $nbCumulativeEmails = 0)
    {
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $processor->expects($this->exactly($nbCumulativeEmails))
            ->method('sendAutoDeactivateEmail');

        $processor->expects($this->exactly($nbDailyEmails))
            ->method('sendAutoDeactivateDailyEmail');

        return $processor;
    }
}
