<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Symfony\Component\Yaml\Yaml;

class LoadWorkflowDefinitionsWithFormConfiguration extends LoadWorkflowDefinitions
{
    const WFC_WORKFLOW_NAME = 'test_workflow_with_form_configuration';
    const WFC_START_TRANSITION = 'start_transition';
    const WFC_TRANSITION = 'transition_1';
    const WFC_STEP_NAME = 'step1';

    /**
     * {@inheritdoc}
     */
    protected function getWorkflowConfiguration()
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/config/oro/workflows_with_form_configuration.yml')) ? : [];
    }
}
