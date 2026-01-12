<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that a workflow is enabled before allowing transition processing.
 *
 * This processor checks whether the workflow is enabled through the feature toggle system.
 * If the workflow is not enabled, it sets a ForbiddenTransitionException error in the context
 * and redirects processing to the normalize group to handle the error appropriately.
 * This ensures that disabled workflows cannot be executed even if directly accessed.
 */
class FeatureCheckProcessor implements ProcessorInterface
{
    /** @var FeatureChecker */
    private $featureChecker;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
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
