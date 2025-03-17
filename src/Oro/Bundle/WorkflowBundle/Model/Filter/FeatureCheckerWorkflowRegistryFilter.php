<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Filters workflow definitions by enabled features.
 */
class FeatureCheckerWorkflowRegistryFilter implements WorkflowDefinitionFilterInterface, SystemFilterInterface
{
    private array $resources = [];

    public function __construct(
        private FeatureChecker $featureChecker
    ) {
    }

    #[\Override]
    public function filter(Collection $workflowDefinitions): Collection
    {
        return $workflowDefinitions->filter(function (WorkflowDefinition $workflowDefinition) {
            $workflowName = $workflowDefinition->getName();
            if (!\array_key_exists($workflowName, $this->resources)) {
                $this->resources[$workflowName] = $this->featureChecker->isResourceEnabled(
                    $workflowDefinition->getName(),
                    FeatureConfigurationExtension::WORKFLOWS_NODE_NAME
                );
            }

            return $this->resources[$workflowName];
        });
    }
}
