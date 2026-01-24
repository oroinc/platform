<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles default form submission for workflow transitions.
 *
 * This processor processes form submissions for transitions using the default form handling mechanism.
 * It handles POST requests by processing the submitted form data, validating it, and persisting
 * the workflow item to the database if the form is valid. The processor sets the saved flag to indicate
 * whether the form submission was successfully processed and persisted.
 */
class DefaultFormProcessor implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        $request = $context->getRequest();

        $context->setSaved(false);
        if ($request->isMethod('POST')) {
            $form = $context->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $context->getWorkflowItem()->setUpdated();
                $this->doctrineHelper->getEntityManager(WorkflowItem::class)->flush();

                $context->setSaved(true);
            }
        }
    }
}
