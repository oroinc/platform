<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ApiFormHandler
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

    /**
     * @param Request       $request
     * @param ObjectManager $manager
     */
    public function __construct(Request $request, ObjectManager $manager)
    {
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * @param FormInterface $form
     */
    public function setForm(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * Process form
     *
     * @param  object $entity
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process($entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
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
     * @param object $entity
     */
    protected function onSuccess($entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
