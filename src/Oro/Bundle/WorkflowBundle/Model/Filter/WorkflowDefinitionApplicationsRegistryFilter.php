<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Filters workflow definitions by the current application.
 */
class WorkflowDefinitionApplicationsRegistryFilter implements WorkflowDefinitionFilterInterface
{
    public function __construct(
        private CurrentApplicationProviderInterface $currentApplicationProvider
    ) {
    }

    #[\Override]
    public function filter(Collection $workflowDefinitions): Collection
    {
        $currentApplication = $this->currentApplicationProvider->getCurrentApplication();
        if (null === $currentApplication) {
            $workflowDefinitions->clear();
        }
        /** @var WorkflowDefinition $workflowDefinition */
        foreach ($workflowDefinitions as $key => $workflowDefinition) {
            if (!\in_array($currentApplication, $workflowDefinition->getApplications(), true)) {
                $workflowDefinitions->remove($key);
            }
        }

        return $workflowDefinitions;
    }
}
