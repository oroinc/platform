<?php

namespace Oro\Bundle\SoapBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;

class ApiFormHandler implements FormAwareInterface
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
        $this->form          = $form;
        $this->request       = $request;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Process form
     *
     * @param mixed $entity
     *
     * @return mixed|null The instance of saved entity on successful processing; otherwise, null
     */
    public function process($entity)
    {
        $entity = $this->prepareFormData($entity);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                return $this->onSuccess($entity) ?: $entity;
            }
        }

        return null;
    }

    /**
     * @param mixed $entity
     *
     * @return mixed The instance of form data object
     */
    protected function prepareFormData($entity)
    {
        $this->form->setData($entity);

        return $entity;
    }

    /**
     * "Success" form handler
     *
     * @param mixed $entity
     *
     * @return mixed|null The instance of saved entity. Can be null if it is equal of the $entity argument
     */
    protected function onSuccess($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
