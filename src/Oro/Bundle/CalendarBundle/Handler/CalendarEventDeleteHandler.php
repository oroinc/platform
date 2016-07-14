<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class CalendarEventDeleteHandler extends DeleteHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EmailSendProcessor */
    protected $emailSendProcessor;

    /**
     * @param RequestStack $requestStack
     *
     * @return CalendarEventDeleteHandler
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

        return $this;
    }

    /**
     * @param EmailSendProcessor $emailSendProcessor
     *
     * @return CalendarEventDeleteHandler
     */
    public function setEmailSendProcessor(EmailSendProcessor $emailSendProcessor)
    {
        $this->emailSendProcessor = $emailSendProcessor;

        return $this;
    }

    /**
     * @param SystemCalendarConfig $calendarConfig
     *
     * @return CalendarEventDeleteHandler
     */
    public function setCalendarConfig(SystemCalendarConfig $calendarConfig)
    {
        $this->calendarConfig = $calendarConfig;

        return $this;
    }

    /**
     * @param SecurityFacade $securityFacade
     *
     * @return CalendarEventDeleteHandler
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        /** @var SystemCalendar|null $calendar */
        $calendar = $entity->getSystemCalendar();
        if ($calendar) {
            if ($calendar->isPublic()) {
                if (!$this->calendarConfig->isPublicCalendarEnabled()) {
                    throw new ForbiddenException('Public calendars are disabled.');
                }

                if (!$this->securityFacade->isGranted('oro_public_calendar_event_management')) {
                    throw new ForbiddenException('Access denied.');
                }
            } else {
                if (!$this->calendarConfig->isSystemCalendarEnabled()) {
                    throw new ForbiddenException('System calendars are disabled.');
                }

                if (!$this->securityFacade->isGranted('oro_system_calendar_event_management')) {
                    throw new ForbiddenException('Access denied.');
                }
            }
        } else {
            parent::checkPermissions($entity, $em);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        /** @var CalendarEvent $entity */
        $entity = $manager->find($id);
        if (!$entity) {
            throw new EntityNotFoundException();
        }

        $em = $manager->getObjectManager();
        $this->processDelete($entity, $em);
    }

    /**
     * @param CalendarEvent $entity
     *
     * {@inheritdoc}
     */
    public function processDelete($entity, ObjectManager $em)
    {
        $this->checkPermissions($entity, $em);

        if ($this->shouldCancelInsteadDelete() && $entity->getRecurringEvent()) {
            $event = $entity->getRealCalendarEvent();
            $event->setCancelled(true);

            $childEvents = $event->getChildEvents();
            foreach ($childEvents as $childEvent) {
                $childEvent->setCancelled(true);
            }
        } else {
            if ($entity->getRecurrence() && $entity->getRecurrence()->getId()) {
                $em->remove($entity->getRecurrence());
            }

            if ($entity->getRecurringEvent()) {
                $event = $entity->getRealCalendarEvent();
                $childEvents = $event->getChildEvents();
                foreach ($childEvents as $childEvent) {
                    $this->deleteEntity($childEvent, $em);
                }
            }
            $this->deleteEntity($entity, $em);
        }

        $em->flush();
        
        if ($this->shouldSendNotification()) {
            $this->emailSendProcessor->sendDeleteEventNotification($entity);
        }
    }
    
    /**
     * @return bool
     */
    protected function shouldSendNotification()
    {
        $request = $this->requestStack->getCurrentRequest();

        return !$request || (bool) $request->query->get('notifyInvitedUsers', false);
    }

    /**
     * @return bool
     */
    protected function shouldCancelInsteadDelete()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request && (bool) $request->query->get('isCancelInsteadDelete', false);
    }
}
