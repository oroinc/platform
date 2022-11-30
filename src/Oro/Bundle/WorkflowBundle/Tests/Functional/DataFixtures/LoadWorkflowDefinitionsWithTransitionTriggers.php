<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Symfony\Component\Yaml\Yaml;

class LoadWorkflowDefinitionsWithTransitionTriggers extends LoadWorkflowDefinitions
{
    protected function getWorkflowConfiguration(): array
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/config/oro/workflows_with_transition_triggers.yml')) ? : [];
    }
}
