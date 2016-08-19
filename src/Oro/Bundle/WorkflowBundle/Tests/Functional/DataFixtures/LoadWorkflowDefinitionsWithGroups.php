<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Symfony\Component\Yaml\Yaml;

class LoadWorkflowDefinitionsWithGroups extends LoadWorkflowDefinitions
{
    /**
     * {@inheritdoc}
     */
    protected function getWorkflowConfiguration()
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/config/workflows_with_groups.yml')) ? : [];
    }
}
