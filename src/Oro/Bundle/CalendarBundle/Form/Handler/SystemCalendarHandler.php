<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

class SystemCalendarHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $manager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager
    ) {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
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
     * @param SystemCalendar $entity
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process(SystemCalendar $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
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
