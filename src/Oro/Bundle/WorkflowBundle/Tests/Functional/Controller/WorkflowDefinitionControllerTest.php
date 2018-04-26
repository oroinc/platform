<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

class WorkflowDefinitionControllerTest extends WebTestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity';

    /**
     * @var WorkflowManager
     */
    private $workflowManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions']);
        $this->workflowManager = $this->client->getContainer()->get('oro_workflow.manager.system');
    }

    public function testIndexAction()
    {
        $response = $this->client->requestGrid(
            ['gridName' => 'workflow-definitions-grid'],
            ['workflow-definitions-grid[_sort_by][createdAt]' => OrmSorterExtension::DIRECTION_DESC],
            true
        );
        $result = $this->getJsonResponseContent($response, 200);
        $workflowList = [];

        foreach ($result['data'] as $item) {
            $workflowList[] = $item['name'];
        }

        $this->assertTrue(in_array(LoadWorkflowDefinitions::MULTISTEP, $workflowList));
        $this->assertTrue(in_array(LoadWorkflowDefinitions::WITH_START_STEP, $workflowList));

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_index', ['workflow-definitions-grid[_pager][_per_page]' => 30]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $this->assertNotEmpty($crawler->html());
        $this->assertContains('workflow-definitions-grid', $crawler->html());
        $this->assertContainGroups($crawler->html());
    }

    public function testUpdateActionForSystem()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_update', [
                'name' => LoadWorkflowDefinitions::WITH_START_STEP,
            ]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testUpdateAction()
    {
        $definition = $this->getWorkflowDefinition(LoadWorkflowDefinitions::WITH_DATAGRIDS);
        $definition->setSystem(false);

        $manager = $this->getObjectManager('OroWorkflowBundle:WorkflowDefinition');
        $manager->flush();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_update', ['name' => LoadWorkflowDefinitions::WITH_DATAGRIDS]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('"availableDestinations":', $crawler->html());
    }

    public function testViewAction()
    {
        //Workaround until BAP-14050 is resolved
        $this->translateStepLabels();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_view', ['name' => LoadWorkflowDefinitions::MULTISTEP]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $this->assertNotEmpty($crawler->html());

        $workflow = $this->workflowManager->getWorkflow(LoadWorkflowDefinitions::MULTISTEP);

        $this->assertContains($workflow->getLabel(), $crawler->html());

        if ($workflow->getStepManager()->getStartStep()) {
            $this->assertContains($workflow->getStepManager()->getStartStep()->getName(), $crawler->html());
        }
    }

    public function testConfigurationAction()
    {
        //Workaround until BAP-14050 is resolved
        $this->translateStepLabels();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_configure', ['name' => LoadWorkflowDefinitions::MULTISTEP]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $this->assertNotEmpty($crawler->html());

        $this->assertContains('Var1Value', $crawler->html());
        $this->assertContains('test_flow', $crawler->html());

        // update variable value
        $form = $crawler->selectButton('Save')->form([
            'oro_workflow_variables[var1]' => 'Var1NewValue',
            'oro_workflow_variables[entity_var]' => 'test_multistep_flow',
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Var1NewValue', $crawler->html());
        $this->assertContains('test_multistep_flow', $crawler->html());
    }

    public function testActivateFormAction()
    {
        $this->workflowManager->activateWorkflow(LoadWorkflowDefinitions::WITH_GROUPS1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_activate_from_widget', [
                '_widgetContainer' => 'dialog',
                '_wid' => uniqid('test', true),
                'name' => LoadWorkflowDefinitions::WITH_GROUPS2,
            ]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $this->assertNotEmpty($crawler->html());
        $this->assertContains(LoadWorkflowDefinitions::WITH_GROUPS2, $crawler->html());
        $this->assertContains('name="oro_workflow_replacement"', $crawler->html());
        $this->assertContains('Activate', $crawler->html());
        $this->assertContains('Cancel', $crawler->html());
        $this->assertContains('The following workflows will be deactivated', $crawler->html());
        $this->assertContains(LoadWorkflowDefinitions::WITH_GROUPS1, $crawler->html());

        $form = $crawler->selectButton('Activate')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertNotEmpty($crawler->html());
    }

    /**
     * @param string $content
     */
    private function assertContainGroups($content)
    {
        $this->assertContains('active_group1', $content);
        $this->assertContains('active_group2', $content);
        $this->assertContains('record_group1', $content);
        $this->assertContains('record_group2', $content);
    }

    /**
     * @param string $name
     * @return WorkflowDefinition
     */
    private function getWorkflowDefinition($name)
    {
        return $this->getObjectManager('OroWorkflowBundle:WorkflowDefinition')
            ->getRepository('OroWorkflowBundle:WorkflowDefinition')
            ->find($name);
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    private function getObjectManager($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }

    /**
     * Translates step labels for test workflow
     */
    private function translateStepLabels()
    {
        $translationsManager = $this->client->getContainer()->get('oro_translation.manager.translation');
        $translationsManager->saveTranslation(
            'oro.workflow.test_multistep_flow.step.starting_point.label',
            'Step starting point',
            'en',
            'workflows'
        );
        $translationsManager->saveTranslation(
            'oro.workflow.test_multistep_flow.step.second_point.label',
            'Second step',
            'en',
            'workflows'
        );
        $translationsManager->saveTranslation(
            'oro.workflow.test_multistep_flow.step.third_point.label',
            'Third step',
            'en',
            'workflows'
        );

        $translationsManager->flush();
        $this->client->getContainer()->get('translator.default')->rebuildCache();
    }
}
