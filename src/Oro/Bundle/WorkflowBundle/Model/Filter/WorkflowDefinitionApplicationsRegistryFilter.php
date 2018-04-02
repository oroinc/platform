<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionApplicationsRegistryFilter implements WorkflowDefinitionFilterInterface
{
    /** @var CurrentApplicationProviderInterface */
    private $currentApplicationProvider;

    /**
     * @param CurrentApplicationProviderInterface $currentApplicationProvider
     */
    public function __construct(CurrentApplicationProviderInterface $currentApplicationProvider)
    {
        $this->currentApplicationProvider = $currentApplicationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Collection $workflowDefinitions)
    {
        $currentApplication = $this->currentApplicationProvider->getCurrentApplication();
        if (null === $currentApplication) {
            $workflowDefinitions->clear();
        }
        /** @var WorkflowDefinition $workflowDefinition */
        foreach ($workflowDefinitions as $key => $workflowDefinition) {
            if (!in_array($currentApplication, $workflowDefinition->getApplications(), true)) {
                $workflowDefinitions->remove($key);
            }
        }

        return $workflowDefinitions;
    }
}
