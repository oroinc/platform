<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

/**
 * @dbIsolation
 */
class WorkflowDefinitionControllerTest extends WebTestCase
{
    const TEST_DEFINITION_NAME = 'TEST_DEFINITION';

    const RELATED_ENTITY = 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity';

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testWorkflowDefinitionPost()
    {
        $workflow = $this->getDefinition(self::TEST_DEFINITION_NAME);
        $this->assertEmpty($workflow);

        $this->client->request(
            'POST',
            $this->getUrl('oro_workflow_api_rest_workflowdefinition_post'),
            $this->getTestConfiguration()
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertContains(self::TEST_DEFINITION_NAME, $result);

        $workflow = $this->getDefinition(self::TEST_DEFINITION_NAME);
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\Workflow', $workflow);
        $this->assertEquals(self::TEST_DEFINITION_NAME, $workflow->getName());
    }

    /**
     * @depends testWorkflowDefinitionPost
     */
    public function testWorkflowDefinitionGet()
    {
        $workflow = $this->getDefinition(self::TEST_DEFINITION_NAME);
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\Workflow', $workflow);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_workflow_api_rest_workflowdefinition_get',
                ['workflowDefinition' => self::TEST_DEFINITION_NAME]
            )
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals($this->getTestConfiguration()['name'], $result['name']);
        $this->assertEquals($this->getTestConfiguration()['entity'], $result['related_entity']);
    }

    /**
     * @depends testWorkflowDefinitionPost
     */
    public function testWorkflowDefinitionPut()
    {
        $workflow = $this->getDefinition(self::TEST_DEFINITION_NAME);
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\Workflow', $workflow);

        $updated = $this->getTestConfiguration();
        $updated['label'] = self::TEST_DEFINITION_NAME . uniqid();
        $this->client->request(
            'PUT',
            $this->getUrl(
                'oro_workflow_api_rest_workflowdefinition_put',
                ['workflowDefinition' => self::TEST_DEFINITION_NAME]
            ),
            $updated
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertContains(self::TEST_DEFINITION_NAME, $result);

        $workflow = $this->getDefinition(self::TEST_DEFINITION_NAME);
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\Workflow', $workflow);
        $this->assertEquals($updated['label'], $workflow->getLabel());
        $this->assertEquals(self::TEST_DEFINITION_NAME, $workflow->getName());
    }

    /**
     * @depends testWorkflowDefinitionPost
     */
    public function testWorkflowDefinitionDelete()
    {
        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_workflow_api_rest_workflowdefinition_delete',
                ['workflowDefinition' => self::TEST_DEFINITION_NAME]
            )
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
        $this->assertEmpty($this->getDefinition(self::TEST_DEFINITION_NAME));
    }

    /**
     * @return array
     */
    public function getTestConfiguration()
    {
        return [
            'steps' => [
                0 => [
                    'name' => 'step:starting_point',
                    'label' => '(Start)',
                    'order' => -1,
                    '_is_start' => true,
                    'is_final' => false,
                    'allowed_transitions' => [
                        0 => 'transition_start_transition_821285e1b12ab',
                    ],
                    '_is_clone' => false,
                    'position' =>
                        [
                            0 => 0,
                            1 => 0,
                        ],
                ],
                1 => [
                    'name' => 'step_start_step_0782b2040a02f',
                    'label' => 'Start Step',
                    'is_final' => true,
                    'order' => 0,
                    'allowed_transitions' => [],
                    '_is_start' => false,
                    '_is_clone' => false,
                    'position' => [
                        0 => -268,
                        1 => -161,
                    ],
                ],
            ],
            'transitions' => [
                0 => [
                    'name' => 'transition_start_transition_821285e1b12ab',
                    'label' => 'Start Transition',
                    'display_type' => 'dialog',
                    'step_to' => 'step_start_step_0782b2040a02f',
                    'is_start' => true,
                    'form_options' => [
                        'attribute_fields' => [],
                    ],
                    'message' => '',
                    'is_unavailable_hidden' => true,
                    'transition_definition' => null,
                    '_is_clone' => false,
                    'frontend_options' => [
                        'icon' => '',
                        'class' => '',
                    ],
                ],
            ],
            'transition_definitions' => [],
            'attributes' => [],
            'name' => self::TEST_DEFINITION_NAME,
            'label' => self::TEST_DEFINITION_NAME,
            'entity' => self::RELATED_ENTITY,
            'entity_attribute' => 'entity',
            'start_step' => '',
            'steps_display_ordered' => true,
        ];
    }

    /**
     * @param $name
     *
     * @return Workflow
     */
    protected function getDefinition($name)
    {
        try {
            return $this->client->getContainer()->get('oro_workflow.manager')->getWorkflow($name);
        } catch (WorkflowNotFoundException $e) {
            return null;
        }
    }
}
