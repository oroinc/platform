<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor;

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

    /**
     * @param FormInterface      $form
     * @param Request            $request
     * @param ObjectManager      $manager
     * @param EmailSendProcessor $emailSendProcessor
     * @param ActivityManager    $activityManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        EmailSendProcessor $emailSendProcessor,
        ActivityManager $activityManager
    ) {
        $this->form               = $form;
        $this->request            = $request;
        $this->manager            = $manager;
        $this->emailSendProcessor = $emailSendProcessor;
        $this->activityManager    = $activityManager;
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

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $originalChildren = new ArrayCollection();
            foreach ($entity->getChildEvents() as $childEvent) {
                $originalChildren->add($childEvent);
            }

            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                // TODO: should be refactored after finishing BAP-8722
                // Contexts handling should be moved to common for activities form handler
                if ($this->form->has('contexts')) {
                    $contexts = $this->form->get('contexts')->getData();
                    $owner = $entity->getCalendar()->getOwner();
                    if ($owner && $owner->getId()) {
                        $contexts = array_merge($contexts, [$owner]);
                    }
                    $this->activityManager->setActivityTargets($entity, $contexts);
                }

                $this->onSuccess(
                    $entity,
                    $originalChildren,
                    $this->form->get('notifyInvitedUsers')->getData()
                );
                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param CalendarEvent   $entity
     * @param ArrayCollection $originalChildren
     * @param boolean         $notify
     */
    protected function onSuccess(
        CalendarEvent $entity,
        ArrayCollection $originalChildren,
        $notify
    ) {
        $new = $entity->getId() ? false : true;
        $this->manager->persist($entity);
        $this->manager->flush();

        if ($new) {
            $this->emailSendProcessor->sendInviteNotification($entity);
        } else {
            $this->emailSendProcessor->sendUpdateParentEventNotification(
                $entity,
                $originalChildren,
                $notify
            );
        }
    }
}
