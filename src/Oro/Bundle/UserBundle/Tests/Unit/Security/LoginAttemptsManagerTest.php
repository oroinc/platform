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
            $this->getLoginAttemptsProvider(3),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );

        $manager->trackLoginFailure($user);

        $this->assertTrue($user->isEnabled());
    }

    public function testIncrementCounterOnFailedLogin()
    {
        $user = $this->getUser(5);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(10),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );

        $manager->trackLoginFailure($user);

        $this->assertSame(6, $user->getFailedLoginCount());
    }

    public function testDeactivateUserOnExeededLimit()
    {
        $user = $this->getUser(4);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(5),
            $this->getUserManager($user),
            $this->getMailProcessor(1)
        );

        $manager->trackLoginFailure($user);

        $this->assertFalse($user->isEnabled());
    }

    public function testResetCounterOnSuccessfulLogin()
    {
        $user = $this->getUser(5);
        $manager = new LoginAttemptsManager(
            $this->getLoginAttemptsProvider(30),
            $this->getUserManager($user),
            $this->getMailProcessor()
        );

        $manager->trackLoginSuccess($user);

        $this->assertSame(0, $user->getFailedLoginCount());
    }

    /**
     * @param int $loginFails
     * @return User
     */
    private function getUser($loginFails = 0)
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setUsername('john');
        $user->setFailedLoginCount($loginFails);

        return $user;
    }

    /**
     * @param int $limit
     * @return LoginAttemptsProvider
     */
    private function getLoginAttemptsProvider($limit = 0)
    {
        $provider = $this->getMockBuilder(LoginAttemptsProvider::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getLimit',
                'hasLimit'
            ])
            ->getMock();

        $provider->expects($this->any())
            ->method('getLimit')
            ->willReturn($limit);

        $provider->expects($this->any())
            ->method('hasLimit')
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
     * @param  int $nbEmails
     * @return Processor
     */
    private function getMailProcessor($nbEmails = 0)
    {
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $processor->expects($this->exactly($nbEmails))
            ->method('sendAutoDeactivateEmail');

        return $processor;
    }
}
