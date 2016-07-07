<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Entity;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CalendarEventTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();
    }

    public function testPreRemoveShouldRemoveRelatedAttendee()
    {
        $em = $this->getEntityManager();

        $child = (new Attendee())
            ->setDisplayName('child');

        $parentEvent = (new CalendarEvent())
            ->setTitle('Parent')
            ->setStart(new \DateTime())
            ->setEnd(new \DateTime())
            ->addAttendee($child)
            ->setRelatedAttendee(
                (new Attendee())
                    ->setDisplayName('parent')
            );
        $em->persist($parentEvent);

        $childEvent = (new CalendarEvent())
            ->setParent($parentEvent)
            ->setStart(new \DateTime())
            ->setEnd(new \DateTime())
            ->setTitle('child')
            ->setRelatedAttendee(
                $child
            );
        $em->persist($childEvent);
        $em->flush();
        $em->refresh($parentEvent);

        $this->assertCount(2, $parentEvent->getAttendees()->toArray());

        $em->remove($parentEvent->getChildEvents()->first());
        $em->flush();
        $em->refresh($parentEvent);

        $this->assertCount(1, $parentEvent->getAttendees());
        $relatedAttendee = $parentEvent->getAttendees()->first();
        $this->assertEquals('parent', $relatedAttendee->getDisplayName());
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroCalendarBundle:CalendarEvent');
    }
}
