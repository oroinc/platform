<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\LoginAttemptsProvider;

class LoginAttemptsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loginAttemptsProvider
     */
    public function testShouldReturnRemainingAttempts(
        $attempts,
        $limit,
        $expected
    ) {
        $user = $this->getUser($attempts);
        $provider = new LoginAttemptsProvider(
            $this->getConfigManager($limit)
        );

        $this->assertSame($expected, $provider->getRemaining($user));
    }

    /**
     * @return array (attempts, limit, expected)
     */
    public function loginAttemptsProvider()
    {
        return [
            'available cumulative logins' => [10, 100, 90],
            'exceed cumulative logins' => [101, 100, 0],
            'always return 0 on exceed' => [100, 70, 0],
            'only cumulative limit' => [7, 10, 3],
            'no limits' => [0, 0, 0],
        ];
    }

    /**
     * @param  int $attempts
     * @return User
     */
    private function getUser($attempts)
    {
        $user = new User();
        $user->setUsername('john_doe');
        $user->setFailedLoginCount($attempts);

        return $user;
    }

    /**
     * @param  int $limit
     * @return ConfigManager
     */
    private function getConfigManager($limit)
    {
        $manager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                [LoginAttemptsProvider::LIMIT_ENABLED, false, false, null, (0 !== $limit)],
                [LoginAttemptsProvider::LIMIT, false, false, null, $limit],
            ]));

        return $manager;
    }
}
