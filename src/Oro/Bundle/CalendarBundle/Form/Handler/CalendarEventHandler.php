<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

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

    /**
     * @param FormInterface       $form
     * @param Request             $request
     * @param ObjectManager       $manager
     * @param ActivityManager     $activityManager
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        ActivityManager $activityManager,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        $this->form                = $form;
        $this->request             = $request;
        $this->manager             = $manager;
        $this->activityManager     = $activityManager;
        $this->entityRoutingHelper = $entityRoutingHelper;
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
     * @return bool  True on successful processing, false otherwise
     */
    public function process(CalendarEvent $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $targetEntityClass = $this->request->get('entityClass');
                if ($targetEntityClass) {
                    $targetEntityId = $this->request->get('entityId');
                    $targetEntity   = $this->entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);
                    $this->activityManager->addActivityTarget($entity, $targetEntity);
                }

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
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
