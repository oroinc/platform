<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Populates initialization data for workflow start transitions.
 *
 * This processor enriches the initialization data for start transitions by retrieving the button
 * search context from the action button provider. This context is stored in the initialization data
 * under the attribute name specified by the transition's init context attribute configuration.
 * This allows workflows to access information about the context in which they were started.
 */
class StartInitDataProcessor implements ProcessorInterface
{
    /** @var ButtonSearchContextProvider */
    private $buttonSearchContextProvider;

    public function __construct(ButtonSearchContextProvider $buttonSearchContextProvider)
    {
        $this->buttonSearchContextProvider = $buttonSearchContextProvider;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        if ($context->getTransition()->isEmptyInitOptions()) {
            return;
        }

        $initData = $context->get(TransitionContext::INIT_DATA);
        $attribute = $context->getTransition()->getInitContextAttribute();

        $initData[$attribute] = $this->buttonSearchContextProvider->getButtonSearchContext();

        $context->set(TransitionContext::INIT_DATA, $initData);
        $context->set(TransitionContext::ENTITY_ID, null);
    }
}
