<?php

namespace Oro\Bundle\WorkflowBundle\Resolver;

use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;

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
