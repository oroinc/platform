<?php

namespace Oro\Bundle\OrganizationBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles organization update
 */
class OrganizationHandler
{
    use RequestHandlerTrait;

    /** @var FormInterface */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var EntityManager */
    protected $manager;

    public function __construct(RequestStack $requestStack, EntityManager $manager)
    {
        $this->requestStack = $requestStack;
        $this->manager = $manager;
    }

    /**
     * Process form
     *
     * @param  Organization $entity
     * @param FormInterface $form
     * @return bool True on successful processing, false otherwise
     */
    public function process(Organization $entity, FormInterface $form)
    {
        $form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($form, $request);
            if ($form->isValid()) {
                $this->onSuccess($entity);
                return true;
            }
        }

        return false;
    }

    protected function onSuccess(Organization $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
