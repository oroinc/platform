<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class StartInitDataProcessor implements ProcessorInterface
{
    /** @var ButtonSearchContextProvider */
    private $buttonSearchContextProvider;

    /**
     * @param ButtonSearchContextProvider $buttonSearchContextProvider
     */
    public function __construct(ButtonSearchContextProvider $buttonSearchContextProvider)
    {
        $this->buttonSearchContextProvider = $buttonSearchContextProvider;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
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
