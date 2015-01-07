<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class LoadCommentData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $calendar = $manager
            ->getRepository('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->findOneBy([]);

        $event = new CalendarEvent();
        $event->setTitle('test_title')
            ->setCalendar($calendar)
            ->setAllDay(true)
            ->setStart(new \DateTime('now -2 days', new \DateTimeZone('UTC')))
            ->setEnd(new \DateTime('now', new \DateTimeZone('UTC')));

        $manager->persist($event);
        $this->setReference('default_activity', $event);

        $manager->flush();
    }
}
