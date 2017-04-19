<?php

namespace Oro\Bundle\WorkflowBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class TransitionFormHandler implements TransitionFormHandlerInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function processStartTransitionForm(
        FormInterface $form,
        WorkflowItem $workflowItem,
        Transition $transition,
        Request $request
    ) {
        $formOptions = $transition->getFormOptions();
        $attributeNames = isset($formOptions['attribute_fields']) ? array_keys($formOptions['attribute_fields']) : [];

        return $this->handleStartTransitionForm($form, $attributeNames, $request);
    }

    /**
     * {@inheritDoc}
     */
    public function processTransitionForm(
        FormInterface $form,
        WorkflowItem $workflowItem,
        Transition $transition,
        Request $request
    ) {
        if ($request->isMethod('POST')) {
            $form->submit($request);

            if ($form->isValid()) {
                $workflowItem->setUpdated();
                $this->doctrineHelper->getEntityManager(WorkflowItem::class)->flush();

                return true;
            }
        }

        return false;
    }

    /**
     * @param FormInterface $transitionForm
     * @param array $attributeNames
     * @param Request $request
     *
     * @return bool
     */
    protected function handleStartTransitionForm(FormInterface $transitionForm, array $attributeNames, Request $request)
    {
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
