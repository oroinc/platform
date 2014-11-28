<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class SystemCalendarHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param FormInterface       $form
     * @param Request             $request
     * @param ObjectManager       $manager
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        SecurityFacade $securityFacade
    ) {
        $this->form                = $form;
        $this->request             = $request;
        $this->manager             = $manager;
        $this->securityFacade      = $securityFacade;
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
     * @param  SystemCalendar $entity
     * @throws \LogicException
     *
     * @return bool  True on successful processing, false otherwise
     */
    public function process(SystemCalendar $entity)
    {
        if (!$entity->getOrganization()) {
            $entity->setOrganization($this->securityFacade->getOrganization());
        }

        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST'))) {
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
     * @param SystemCalendar $entity
     */
    protected function onSuccess(SystemCalendar $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
