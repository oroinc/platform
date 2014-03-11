<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarConnection;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Notification\RemindTimeCalculator;
use Oro\Bundle\UserBundle\Entity\User;

class EntitySubscriber implements EventSubscriber
{
    /**
     * @var RemindTimeCalculator
     */
    protected $remindTimeCalculator;

    /**
     * @var ClassMetadata
     */
    protected $calendarMetadata;

    /**
     * @var ClassMetadata
     */
    protected $calendarConnectionMetadata;

    /**
     * @param RemindTimeCalculator $remindTimeCalculator
     */
    public function __construct(RemindTimeCalculator $remindTimeCalculator)
    {
        $this->remindTimeCalculator = $remindTimeCalculator;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [Events::onFlush];
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $em  = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof User) {
                // create a default calendar to a new user
                $calendar = new Calendar();
                $calendar->setOwner($entity);
                // connect the calendar to itself
                $calendarConnection = new CalendarConnection($calendar);
                $calendar->addConnection($calendarConnection);

                $em->persist($calendar);
                $em->persist($calendarConnection);
                // can't inject entity manager through constructor because of circular dependency
                $uow->computeChangeSet($this->getCalendarMetadata($em), $calendar);
                $uow->computeChangeSet($this->getCalendarConnectionMetadata($em), $calendarConnection);
            }
        }
    }

    /**
     * @param EntityManager $entityManager
     * @return ClassMetadata
     */
    protected function getCalendarMetadata(EntityManager $entityManager)
    {
        if (!$this->calendarMetadata) {
            $this->calendarMetadata = $entityManager->getClassMetadata('OroCalendarBundle:Calendar');
        }

        return $this->calendarMetadata;
    }

    /**
     * @param EntityManager $entityManager
     * @return ClassMetadata
     */
    protected function getCalendarConnectionMetadata(EntityManager $entityManager)
    {
        if (!$this->calendarConnectionMetadata) {
            $this->calendarConnectionMetadata
                = $entityManager->getClassMetadata('OroCalendarBundle:CalendarConnection');
        }

        return $this->calendarConnectionMetadata;
    }
}
