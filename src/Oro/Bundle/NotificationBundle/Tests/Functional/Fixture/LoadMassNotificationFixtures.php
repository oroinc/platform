<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class LoadMassNotificationFixtures extends AbstractFixture
{
    /** @var ObjectManager */
    protected $em;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;

        $this->createMassNotification();
    }

    protected function createMassNotification()
    {
        $date = new \DateTime();
        $notification = new MassNotification();
        $notification->setEmail('to@test.com');
        $notification->setSubject('test');
        $notification->setBody('test body');
        $notification->setSender('from@test.com');
        $notification->setScheduledAt($date);
        $notification->setProcessedAt($date);
        $notification->setStatus(1);
        $this->em->persist($notification);
        $this->em->flush();

        $this->setReference('mass_notification', $notification);

        return $this;
    }
}
