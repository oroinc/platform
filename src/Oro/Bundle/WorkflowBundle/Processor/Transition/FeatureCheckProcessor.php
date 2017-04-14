<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class FeatureCheckProcessor implements ProcessorInterface
{
    /** @var FeatureChecker */
    private $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        if ($context->hasError()) {
            return;
        }

        $nodeName = FeatureConfigurationExtension::WORKFLOWS_NODE_NAME;

        if (!$this->featureChecker->isResourceEnabled($context->getWorkflowName(), $nodeName)) {
            $context->setError(new ForbiddenTransitionException());
            $context->setFirstGroup('normalize');
        }
    }
}
