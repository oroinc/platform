<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\LoginHistoryManager;

class LoginHistoryManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loginProvider
     */
    public function testShouldLogSuccessfulUserLogin($isSuccessful, $expected)
    {
        $user = new User();
        $manager = $this->getLoginHistoryManager();

        $history = $manager->logUserLogin($user, $isSuccessful);

        $this->assertSame($user, $history->getUser());
        $this->assertSame($expected, $history->isSuccessful());
    }

    /**
     * @return array (isSuccessful, expected)
     */
    public function loginProvider()
    {
        return [
            'successful' => [true, true],
            'failed' => [false, false],
        ];
    }

    /**
     * @param  int $persistCalls
     * @param  int $flushCalls
     * @return LoginHistoryManager
     */
    private function getLoginHistoryManager($persistCalls = 1, $flushCalls = 1)
    {
        return new LoginHistoryManager($this->getObjectManager($persistCalls, $flushCalls));
    }

    /**
     * @param  int $persistCalls
     * @param  int $flushCalls
     * @return ObjectManager
     */
    private function getObjectManager($persistCalls, $flushCalls)
    {
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->exactly($persistCalls))
            ->method('persist');

        $manager->expects($this->exactly($flushCalls))
            ->method('flush');

        return $manager;
    }
}
