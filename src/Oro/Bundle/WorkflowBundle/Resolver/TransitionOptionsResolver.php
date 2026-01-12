<?php

namespace Oro\Bundle\WorkflowBundle\Resolver;

use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;

/**
 * Resolves transition frontend options based on workflow item context.
 *
 * This resolver evaluates transition options using the current workflow item state,
 * allowing dynamic configuration of transition display and behavior.
 */
class TransitionOptionsResolver
{
    /** @var OptionsResolver */
    protected $optionsResolver;

    public function __construct(OptionsResolver $optionsResolver)
    {
        $this->optionsResolver = $optionsResolver;
    }

    public function resolveTransitionOptions(Transition $transition, WorkflowItem $workflowItem)
    {
        $frontendOptions = $transition->getFrontendOptions();
        if (!empty($frontendOptions)) {
            $transition->setFrontendOptions(
                $this->optionsResolver->resolveOptions($workflowItem, $frontendOptions)
            );
        }
    }
}
