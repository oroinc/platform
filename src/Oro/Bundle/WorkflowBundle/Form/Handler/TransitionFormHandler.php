<?php

namespace Oro\Bundle\WorkflowBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class TransitionFormHandler
{
    /** @var RequestStack */
    private $requestStack;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param RequestStack $requestStack
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(RequestStack $requestStack, DoctrineHelper $doctrineHelper)
    {
        $this->requestStack = $requestStack;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Form $transitionForm
     * @param array $attributeNames
     * @return bool
     */
    public function handleTransitionForm(Form $transitionForm, array $attributeNames)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request->isMethod('POST')) {
            return false;
        }
        $doctrineHelper = $this->doctrineHelper;
        $transitionForm->submit($request);
        if ($transitionForm->isValid()) {
            $formAttributes = $transitionForm->getData()->getValues($attributeNames);

            foreach ($formAttributes as $value) {
                // Need to persist all new entities to allow serialization
                // and correct passing to API start method of all input data.
                // Form validation already performed, so all these entities are valid
                // and they can be used in workflow start action.
                if (is_object($value) && $doctrineHelper->isManageableEntity($value)) {
                    $entityManager = $doctrineHelper->getEntityManager($value);
                    $unitOfWork = $entityManager->getUnitOfWork();
                    if (!$unitOfWork->isInIdentityMap($value) || $unitOfWork->isScheduledForInsert($value)) {
                        $entityManager->persist($value);
                        $entityManager->flush($value);
                    }
                }
            }
            return true;
        }
        return false;
    }
}
