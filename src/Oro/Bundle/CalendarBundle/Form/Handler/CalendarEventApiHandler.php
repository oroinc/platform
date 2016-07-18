<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventApiHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var EmailSendProcessor */
    protected $emailSendProcessor;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /**
     * @param FormInterface           $form
     * @param Request                 $request
     * @param ObjectManager           $manager
     * @param EmailSendProcessor      $emailSendProcessor
     * @param ActivityManager         $activityManager
     * @param AttendeeRelationManager $attendeeRelationManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        EmailSendProcessor $emailSendProcessor,
        ActivityManager $activityManager,
        AttendeeRelationManager $attendeeRelationManager
    ) {
        $this->form                    = $form;
        $this->request                 = $request;
        $this->manager                 = $manager;
        $this->emailSendProcessor      = $emailSendProcessor;
        $this->activityManager         = $activityManager;
        $this->attendeeRelationManager = $attendeeRelationManager;
    }

    /**
     * Process form
     *
     * @param  CalendarEvent $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process(CalendarEvent $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            // clone attendees to have have original attendees at disposal later
            $originalAttendees = new ArrayCollection($entity->getAttendees()->toArray());
            $this->form->submit($this->request->request->all());

            if ($this->form->isValid()) {
                /** @deprecated since version 1.10. Please use field attendees instead of invitedUsers */
                if ($this->form->has('invitedUsers')) {
                    $this->convertInvitedUsersToAttendees($entity, $this->form->get('invitedUsers')->getData());
                }

                // TODO: should be refactored after finishing BAP-8722
                // Contexts handling should be moved to common for activities form handler
                if ($this->form->has('contexts') && $this->request->request->has('contexts')) {
                    $contexts = $this->form->get('contexts')->getData();
                    $owner = $entity->getCalendar()->getOwner();
                    if ($owner && $owner->getId()) {
                        $contexts = array_merge($contexts, [$owner]);
                    }
                    $this->activityManager->setActivityTargets($entity, $contexts);
                } elseif (!$entity->getId() && $entity->getRecurringEvent()) {
                    $this->activityManager->setActivityTargets(
                        $entity,
                        $entity->getRecurringEvent()->getActivityTargetEntities()
                    );
                }

                $this->onSuccess(
                    $entity,
                    $originalAttendees,
                    $this->shouldBeNotified()
                );
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated since version 1.10. Please use field attendees instead of invitedUsers
     *
     * @param CalendarEvent $event
     * @param User[]        $users
     */
    protected function convertInvitedUsersToAttendees(CalendarEvent $event, array $users)
    {
        foreach ($users as $user) {
            $attendee = $this->attendeeRelationManager->createAttendee($user);

            if ($attendee) {
                $status = $this->manager
                    ->getRepository(ExtendHelper::buildEnumValueClassName(Attendee::STATUS_ENUM_CODE))
                    ->find(Attendee::STATUS_NONE);
                $attendee->setStatus($status);

                $type = $this->manager
                    ->getRepository(ExtendHelper::buildEnumValueClassName(Attendee::TYPE_ENUM_CODE))
                    ->find(Attendee::TYPE_REQUIRED);
                $attendee->setType($type);

                $event->addAttendee($attendee);
            }
        }
    }

    /**
     * "Success" form handler
     *
     * @param CalendarEvent              $entity
     * @param ArrayCollection|Attendee[] $originalAttendees
     * @param boolean                    $notify
     */
    protected function onSuccess(
        CalendarEvent $entity,
        ArrayCollection $originalAttendees,
        $notify
    ) {
        $new = $entity->getId() ? false : true;
        if ($entity->isCancelled()) {
            $event = $entity->getRealCalendarEvent();
            $childEvents = $event->getChildEvents();
            foreach ($childEvents as $childEvent) {
                $childEvent->setCancelled(true);
            }
        }
        $this->manager->persist($entity);
        $this->manager->flush();

        if ($notify) {
            if ($new) {
                $this->emailSendProcessor->sendInviteNotification($entity);
            } else {
                $this->emailSendProcessor->sendUpdateParentEventNotification(
                    $entity,
                    $originalAttendees,
                    $notify
                );
            }
        }
    }

    /**
     * @return bool
     */
    protected function shouldBeNotified()
    {
        $notifyInvitedUsers = $this->form->get('notifyInvitedUsers')->getData();

        return $notifyInvitedUsers === 'true' || $notifyInvitedUsers === true;
    }
}
