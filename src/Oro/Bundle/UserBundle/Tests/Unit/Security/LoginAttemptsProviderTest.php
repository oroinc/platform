<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\Repository\LoginHistoryRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;

class LoginAttemptsProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnNullForUnknownUsername()
    {
        $provider = new LoginAttemptsProvider(
            $this->getDoctrine(1, 2),
            $this->getConfigManager(10, 20),
            $this->getUserManager(null)
        );

        $this->assertNull($provider->getByUsername('john_doe'));
    }

    public function testShouldReturnAttemptsByUsername()
    {
        $provider = new LoginAttemptsProvider(
            $this->getDoctrine(1, 2),
            $this->getConfigManager(10, 20),
            $this->getUserManager(new User())
        );

        $this->assertSame(9, $provider->getByUser(new User()));
    }

    /**
     * @dataProvider loginAttemptsProvider
     */
    public function testShouldReturnRemainingLoginAttemptsPerUser(
        $dailyAttempts,
        $cumutativeAttempts,
        $dailyLimit,
        $cumulativeLimit,
        $expected
    ) {
        $provider = new LoginAttemptsProvider(
            $this->getDoctrine($dailyAttempts, $cumutativeAttempts),
            $this->getConfigManager($dailyLimit, $cumulativeLimit),
            $this->getUserManager(new User())
        );

        $this->assertSame($expected, $provider->getByUser(new User()));
    }

    /**
     * @return array (dailyAttempts, cumulativeAttempts, dailyLimit, cumulativeLimit, expected)
     */
    public function loginAttemptsProvider()
    {
        return [
            'available daily logins' => [7, 10, 10, 99, 3],
            'available cumulative logins' => [3, 10, 99, 100, 90],
            'exceed daily logins' => [3, 5, 3, 99, 0],
            'exceed cumulative logins' => [1, 101, 99, 100, 0],
            'always return 0 on exceed' => [5, 100, 10, 70, 0],
        ];
    }

    /**
     * @param  int $maxAttempts
     * @param  int $maxDailyAttempts
     * @return ConfigManager
     */
    private function getConfigManager($maxAttempts, $maxDailyAttempts)
    {
        $manager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                [LoginAttemptsProvider::MAX_LOGIN_ATTEMPTS, false, false, null, $maxAttempts],
                [LoginAttemptsProvider::MAX_DAILY_LOGIN_ATTEMPTS, false, false, null, $maxDailyAttempts]
            ]));

        return $manager;
    }

    /**
     * @param  int $cumutativeLogins
     * @param  int $dailyLogins
     * @return Registry
     */
    private function getDoctrine($cumutativeLogins, $dailyLogins)
    {
        $repository = $this->getMockBuilder(LoginHistoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('countUserCumulativeFailedLogins')
            ->willReturn($cumutativeLogins);

        $repository->expects($this->any())
            ->method('countUserDailyFailedLogins')
            ->willReturn($dailyLogins);

        $registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        return $registry;
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
            ->method('findUserByUsername')
            ->willReturn($user);

        return $manager;
    }
}
