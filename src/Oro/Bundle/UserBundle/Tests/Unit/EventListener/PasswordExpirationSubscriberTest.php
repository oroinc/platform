<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\EventListener\PasswordExpirationSubscriber;
use Oro\Bundle\UserBundle\Provider\PasswordChangePeriodConfigProvider;

class PasswordExpirationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatingUserWillSetExpirationDate()
    {
        $user = new User();
        $this->getListener('2016-11-03')
            ->prePersist($this->getEventArgs($user));

        $this->assertSame('2016-11-03', $user->getPasswordExpiresAt()->format('Y-m-d'));
    }

    public function testChaningPasswordWillChangeExpirationDate()
    {
        $user = new User();
        $this->getListener('2016-11-03')
            ->preUpdate($this->getEventArgs($user, ['password' => []]));

        $this->assertSame('2016-11-03', $user->getPasswordExpiresAt()->format('Y-m-d'));
    }

    /**
     * @param string $date
     * @return PasswordChangePeriodConfigProvider
     */
    private function getProvider($date)
    {
        $expireDate = new \DateTime($date, new \DateTimeZone('UTC'));
        $provider = $this->getMockBuilder(PasswordChangePeriodConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->any())
            ->method('getPasswordExpiryDateFromNow')
            ->willReturn($expireDate);

        return $provider;
    }

    /**
     * @param string $date
     * @return PasswordExpirationSubscriber
     */
    private function getListener($date)
    {
        return new PasswordExpirationSubscriber($this->getProvider($date));
    }

    /**
     * @param User $user
     * @param array $changeSet
     * @return LifecycleEventArgs
     */
    private function getEventArgs(User $user, array $changeSet = [])
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (empty($changeSet)) {
            return new LifecycleEventArgs($user, $em);
        }

        return new PreUpdateEventArgs($user, $em, $changeSet);
    }
}
