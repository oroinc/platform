<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutPageResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Translation\Helper\TransitionTranslationHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Processes transition context to prepare layout page result data.
 *
 * This processor builds the result data structure for page-based workflow transitions,
 * including workflow and transition information, form view, and workflow item details.
 */
class LayoutPageDataTransitionProcessor implements ProcessorInterface
{
    /** @var TransitionTranslationHelper */
    protected $helper;

    public function __construct(TransitionTranslationHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        $resultType = $context->getResultType();

        if (!$resultType instanceof LayoutPageResultType) {
            return;
        }

        $this->helper->processTransitionTranslations($context->getTransition());

        $context->setResult(
            [
                'workflowName' => $context->getWorkflow()->getLabel(),
                'transitionName' => $context->getTransitionName(),
                'data' => [
                    'transitionFormView' => $context->getForm()->createView(),
                    'transition' => $context->getTransition(),
                    'workflowItem' => $context->getWorkflowItem(),
                    'formRouteName' => $resultType->getFormRouteName(),
                    'originalUrl' => $context->getRequest()->get('originalUrl', '/'),
                ]
            ]
        );
        $context->setProcessed(true);
    }
}
