<?php

namespace Oro\Bundle\SoapBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

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
    protected $entityManager;

    /**
     *
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $entityManager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $entityManager)
    {
        $this->form    = $form;
        $this->request = $request;
        $this->entityManager = $entityManager;
    }

    /**
     * Process form
     *
     * @param  mixed $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process($entity)
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
     * @param mixed $entity
     */
    protected function onSuccess($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
