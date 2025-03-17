<?php

namespace Oro\Bundle\OrganizationBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
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

    public function __construct(
        protected RequestStack $requestStack,
        protected ManagerRegistry $doctrine
    ) {
    }

    public function process(Organization $entity, FormInterface $form): bool
    {
        $form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (\in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($form, $request);
            if ($form->isValid()) {
                $this->onSuccess($entity);
                return true;
            }
        }

        return false;
    }

    protected function onSuccess(Organization $entity): void
    {
        $em = $this->doctrine->getManagerForClass(Organization::class);
        $em->persist($entity);
        $em->flush();
    }
}
