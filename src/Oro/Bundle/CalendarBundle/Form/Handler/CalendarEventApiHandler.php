<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class CalendarEventApiHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /** @var CalendarEvent */
    protected $dirtyEntity;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager)
    {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
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
            $this->dirtyEntity = $entity;

            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($entity);
                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param CalendarEvent $entity
     */
    protected function onSuccess(CalendarEvent $entity)
    {
        $new = $entity->getId() ? false : true;

        $this->manager->persist($entity);
        $this->manager->flush();

        if ($new) {
            $this->emailSendProcessor->sendInviteNotification($entity);
        } else {
            $this->emailSendProcessor->sendUpdateEventNotification($entity, $this->dirtyEntity);
        }
    }
}
