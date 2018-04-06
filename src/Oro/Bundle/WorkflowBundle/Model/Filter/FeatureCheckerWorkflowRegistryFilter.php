<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class FeatureCheckerWorkflowRegistryFilter implements WorkflowDefinitionFilterInterface, SystemFilterInterface
{
    /** @var FeatureChecker */
    private $featureChecker;

    /** @var array */
    private $resources = [];

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Collection $workflowDefinitions)
    {
        return $workflowDefinitions->filter(function (WorkflowDefinition $workflowDefinition) {
            $workflowName = $workflowDefinition->getName();

            if (!array_key_exists($workflowName, $this->resources)) {
                $this->resources[$workflowName] = $this->featureChecker->isResourceEnabled(
                    $workflowDefinition->getName(),
                    FeatureConfigurationExtension::WORKFLOWS_NODE_NAME
                );
            }

            return $this->resources[$workflowName];
        });
    }
}
