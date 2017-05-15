<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutPageResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Translation\Helper\TransitionTranslationHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class LayoutPageDataStartTransitionProcessor implements ProcessorInterface
{
    /** @var TransitionTranslationHelper */
    protected $helper;

    /**
     * @param TransitionTranslationHelper $helper
     */
    public function __construct(TransitionTranslationHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $resultType = $context->getResultType();

        if (!$resultType instanceof LayoutPageResultType) {
            return;
        }

        $this->helper->processTransitionTranslations($context->getTransition());

        $transitionName = $context->getTransitionName();
        $request = $context->getRequest();

        $context->setResult(
            [
                'workflowName' => $context->getWorkflow()->getLabel(),
                'transitionName' => $transitionName,
                'data' => [
                    'transitionFormView' => $context->getForm()->createView(),
                    'workflowName' => $context->getWorkflowName(),
                    'workflowItem' => $context->getWorkflowItem(),
                    'transitionName' => $transitionName,
                    'transition' => $context->getTransition(),
                    'entityId' => $request->get('entityId', 0),
                    'originalUrl' => $request->get('originalUrl', '/'),
                    'formRouteName' => $resultType->getFormRouteName(),
                ]
            ]
        );
        $context->setProcessed(true);
    }
}
