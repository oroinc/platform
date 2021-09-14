<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutDialogResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Sets JsonResponse with WorkflowResult as a TransitionContext result data.
 */
class FormSubmitLayoutAjaxResponseProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        if (!$this->isApplicable($context)) {
            return;
        }

        $data = [
            'workflowItem' => ['result' => $context->getWorkflowItem()->getResult()->toArray()]
        ];

        $context->setResult(new JsonResponse($data));
        $context->setProcessed(true);
    }

    protected function isApplicable(TransitionContext $context): bool
    {
        if (!$context->isSaved()) {
            return false;
        }

        if (!$context->getResultType() instanceof LayoutDialogResultType) {
            return false;
        }

        if (!$context->getRequest()->isXmlHttpRequest()) {
            return false;
        }

        return true;
    }
}
