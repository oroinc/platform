<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;

class DefaultFormStartHandleProcessor implements ProcessorInterface
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
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $request = $context->getRequest();

        $context->setSaved(false);

        if ($request->isMethod('POST')) {
            $form = $context->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $formAttributes = $this->getFormAttributes($form, $context->getTransition());
                foreach ($formAttributes as $value) {
                    // Need to persist all new entities to allow serialization
                    // and correct passing to API start method of all input data.
                    // Form validation already performed, so all these entities are valid
                    // and they can be used in workflow start action.
                    if (is_object($value) && $this->doctrineHelper->isManageableEntity($value)) {
                        $entityManager = $this->doctrineHelper->getEntityManager($value);
                        $unitOfWork = $entityManager->getUnitOfWork();
                        if (!$unitOfWork->isInIdentityMap($value) || $unitOfWork->isScheduledForInsert($value)) {
                            $entityManager->persist($value);
                            $entityManager->flush($value);
                        }
                    }
                }
                $context->setSaved(true);
            }
        }
    }

    /**
     * @param FormInterface $form
     * @param Transition $transition
     * @return array
     */
    protected function getFormAttributes(FormInterface $form, Transition $transition): array
    {
        $formOptions = $transition->getFormOptions();
        $attributeNames = isset($formOptions['attribute_fields']) ? array_keys($formOptions['attribute_fields']) : [];

        return $form->getData()->getValues($attributeNames);
    }
}
