<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Template;

use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Processes transition context to prepare common template result data.
 *
 * This processor builds the result data structure for template-based workflow transitions,
 * including transition information, form view, and form errors.
 */
class CommonTemplateDataProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        if (!$this->isApplicable($context)) {
            return;
        }

        $transitionForm = $context->getForm();

        $context->set(
            'template_parameters',
            [
                'transition' => $context->getTransition(),
                'workflowItem' => $context->getWorkflowItem(),
                'saved' => false,
                'form' => $transitionForm->createView(),
                'formErrors' => $transitionForm->getErrors(true)
            ]
        );
    }

    protected function isApplicable(TransitionContext $context): bool
    {
        if (!$context->getResultType() instanceof TemplateResultType) {
            return false;
        }

        if ($context->isSaved()) {
            return false;
        }

        return true;
    }
}
