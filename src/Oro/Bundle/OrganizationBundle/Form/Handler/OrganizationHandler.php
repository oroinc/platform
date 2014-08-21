<?php

namespace Oro\Bundle\OrganizationBundle\Form\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var EntityManager */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param EntityManager $manager
     */
    public function __construct(FormInterface $form, Request $request, EntityManager $manager)
    {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * Process form
     *
     * @param  Organization $entity
     * @return bool True on successful processing, false otherwise
     */
    public function process(Organization $entity)
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
     * @param Organization $entity
     */
    protected function onSuccess(Organization $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
