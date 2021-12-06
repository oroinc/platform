<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

class WorkflowDefinitionControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWorkflowDefinitions::class]);
    }

    private function getWorkflowManager(): WorkflowManager
    {
        return self::getContainer()->get('oro_workflow.manager.system');
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

        $this->assertContains(LoadWorkflowDefinitions::MULTISTEP, $workflowList);
        $this->assertContains(LoadWorkflowDefinitions::WITH_START_STEP, $workflowList);

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
        self::assertStringContainsString('workflow-definitions-grid', $crawler->html());
        $content = $crawler->html();
        self::assertStringContainsString('active_group1', $content);
        self::assertStringContainsString('active_group2', $content);
        self::assertStringContainsString('record_group1', $content);
        self::assertStringContainsString('record_group2', $content);
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
        /** @var WorkflowDefinition $definition */
        $definition = self::getContainer()->get('doctrine')->getRepository(WorkflowDefinition::class)
            ->find(LoadWorkflowDefinitions::WITH_DATAGRIDS);
        $definition->setSystem(false);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowDefinition::class);
        $em->flush();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_update', ['name' => LoadWorkflowDefinitions::WITH_DATAGRIDS]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('"availableDestinations":', $crawler->html());
    }

    public function testViewAction()
    {
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

        $workflow = $this->getWorkflowManager()->getWorkflow(LoadWorkflowDefinitions::MULTISTEP);

        self::assertStringContainsString($workflow->getLabel(), $crawler->html());

        if ($workflow->getStepManager()->getStartStep()) {
            self::assertStringContainsString(
                $workflow->getStepManager()->getStartStep()->getName(),
                $crawler->html()
            );
        }
    }

    public function testConfigurationAction()
    {
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

        self::assertStringContainsString('Var1Value', $crawler->html());
        self::assertStringContainsString('test_flow', $crawler->html());

        // update variable value
        $form = $crawler->selectButton('Save')->form([
            'oro_workflow_variables[var1]' => 'Var1NewValue',
            'oro_workflow_variables[entity_var]' => 'test_multistep_flow',
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('Var1NewValue', $crawler->html());
        self::assertStringContainsString('test_multistep_flow', $crawler->html());
    }

    public function testActivateFormAction()
    {
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::WITH_GROUPS1);

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
        self::assertStringContainsString(LoadWorkflowDefinitions::WITH_GROUPS2, $crawler->html());
        self::assertStringContainsString('name="oro_workflow_replacement"', $crawler->html());
        self::assertStringContainsString('Activate', $crawler->html());
        self::assertStringContainsString('Cancel', $crawler->html());
        self::assertStringContainsString('The following workflows will be deactivated', $crawler->html());
        self::assertStringContainsString(LoadWorkflowDefinitions::WITH_GROUPS1, $crawler->html());

        $form = $crawler->selectButton('Activate')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertNotEmpty($crawler->html());
    }
}
