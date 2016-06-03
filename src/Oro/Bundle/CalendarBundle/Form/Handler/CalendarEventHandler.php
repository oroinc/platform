<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CalendarEventHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EmailSendProcessor */
    protected $emailSendProcessor;

    /**
     * @param FormInterface       $form
     * @param Request             $request
     * @param ObjectManager       $manager
     * @param ActivityManager     $activityManager
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param SecurityFacade      $securityFacade
     * @param EmailSendProcessor  $emailSendProcessor
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        ActivityManager $activityManager,
        EntityRoutingHelper $entityRoutingHelper,
        SecurityFacade $securityFacade,
        EmailSendProcessor $emailSendProcessor
    ) {
        $this->form                = $form;
        $this->request             = $request;
        $this->manager             = $manager;
        $this->activityManager     = $activityManager;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->securityFacade      = $securityFacade;
        $this->emailSendProcessor  = $emailSendProcessor;
    }

    /**
     * Get form, that build into handler, via handler service
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Process form
     *
     * @param  CalendarEvent $entity
     *
     * @throws \LogicException
     *
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
                $this->ensureCalendarSet($entity);

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

                $targetEntityClass = $this->entityRoutingHelper->getEntityClassName($this->request);
                if ($targetEntityClass) {
                    $targetEntityId = $this->entityRoutingHelper->getEntityId($this->request);
                    $targetEntity   = $this->entityRoutingHelper->getEntityReference(
                        $targetEntityClass,
                        $targetEntityId
                    );

                    $action = $this->entityRoutingHelper->getAction($this->request);
                    if ($action === 'activity') {
                        $this->activityManager->addActivityTarget($entity, $targetEntity);
                    }

                    if ($action === 'assign'
                        && $targetEntity instanceof User
                        && $targetEntityId !== $this->securityFacade->getLoggedUserId()
                    ) {
                        /** @var Calendar $defaultCalendar */
                        $defaultCalendar = $this->manager
                            ->getRepository('OroCalendarBundle:Calendar')
                            ->findDefaultCalendar($targetEntity->getId(), $targetEntity->getOrganization()->getId());
                        $entity->setCalendar($defaultCalendar);
                    }
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
     * @param CalendarEvent $entity
     *
     * @throws \LogicException
     */
    protected function ensureCalendarSet(CalendarEvent $entity)
    {
        if ($entity->getCalendar() || $entity->getSystemCalendar()) {
            return;
        }
        if (!$this->securityFacade->getLoggedUser() || !$this->securityFacade->getOrganization()) {
            throw new \LogicException('Both logged in user and organization must be defined.');
        }

        /** @var Calendar $defaultCalendar */
        $defaultCalendar = $this->manager
            ->getRepository('OroCalendarBundle:Calendar')
            ->findDefaultCalendar(
                $this->securityFacade->getLoggedUser()->getId(),
                $this->securityFacade->getOrganization()->getId()
            );
        $entity->setCalendar($defaultCalendar);
    }

    /**
     * "Success" form handler
     *
     * @param CalendarEvent   $entity
     * @param ArrayCollection $originalChildren
     * @param boolean         $notify
     */
    protected function onSuccess(CalendarEvent $entity, ArrayCollection $originalChildren, $notify)
    {
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
