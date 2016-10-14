<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;

class LoginAttemptsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider exceedLoginAttemptsProvider
     */
    public function testShouldReturnExceededLimitPerUser(
        $dailyAttempts,
        $cumulativeAttempts,
        $dailyLimit,
        $cumulativeLimit,
        $expected
    ) {
        $user = $this->getUser($dailyAttempts, $cumulativeAttempts);
        $provider = new LoginAttemptsProvider(
            $this->getConfigManager($dailyLimit, $cumulativeLimit)
        );

        $this->assertSame($expected, $provider->getExceedLimit($user));
    }

    /**
     * @return array (dailyAttempts, cumulativeAttempts, dailyLimit, cumulativeLimit, expected)
     */
    public function exceedLoginAttemptsProvider()
    {
        return [
            'exceed daily logins' => [12, 10, 10, 99, 10],
            'exceed cumulative logins' => [3, 25, 99, 20, 20],
            'does not exceed any limits' => [3, 5, 99, 99, 0],
        ];
    }

    /**
     * @dataProvider loginAttemptsProvider
     */
    public function testShouldReturnRemainingLoginAttemptsPerUser(
        $dailyAttempts,
        $cumulativeAttempts,
        $dailyLimit,
        $cumulativeLimit,
        $expected
    ) {
        $user = $this->getUser($dailyAttempts, $cumulativeAttempts);
        $provider = new LoginAttemptsProvider(
            $this->getConfigManager($dailyLimit, $cumulativeLimit)
        );

        $this->assertSame($expected, $provider->getByUser($user));
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
     * @param  int $dailyAttempts
     * @param  int $cumulativeAttempts
     * @return User
     */
    private function getUser($dailyAttempts, $cumulativeAttempts)
    {
        $user = new User();
        $user->setUsername('john_doe');
        $user->setDailyFailedLoginCount($dailyAttempts);
        $user->setFailedLoginCount($cumulativeAttempts);

        return $user;
    }

    /**
     * @param  int $maxDailyAttempts
     * @param  int $maxAttempts
     * @return ConfigManager
     */
    private function getConfigManager($maxDailyAttempts, $maxAttempts)
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
}
