<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolation
 */
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
        $this->workflowManager = $this->client->getContainer()->get('oro_workflow.manager');
    }

    public function testIndexAction()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_index'),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $this->assertNotEmpty($crawler->html());
        $this->assertContains(LoadWorkflowDefinitions::MULTISTEP, $crawler->html());
        $this->assertContains(LoadWorkflowDefinitions::WITH_START_STEP, $crawler->html());
    }

//    public function testUpdateAction()
//    {
//        $crawler = $this->client->request(
//            'GET',
//            $this->getUrl('oro_workflow_definition_update', [
//                'name' => LoadWorkflowDefinitions::MULTISTEP,
//            ]),
//            [],
//            [],
//            $this->generateBasicAuthHeader()
//        );
//        $response = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($response, 200);
//
//        $this->assertNotEmpty($crawler->html());
//
//        $form = $crawler->selectButton('Save and Close')->form();
//
//        $this->client->followRedirects(true);
//        $crawler = $this->client->submit($form);
//
//        $result = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//        $this->assertNotEmpty($crawler->html());
//    }

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
        $this->assertJsonResponseStatusCodeEquals($response, 403);
    }

//    /**
//     * @depends testUpdateAction
//     */
//    public function testCloneAction()
//    {
//        $crawler = $this->client->request(
//            'GET',
//            $this->getUrl('oro_workflow_definition_clone',['name' => LoadWorkflowDefinitions::MULTISTEP]),
//            [],
//            [],
//            $this->generateBasicAuthHeader()
//        );
//        $response = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($response, 200);
//
//        $this->assertNotEmpty($crawler->html());
//        $this->assertContains(LoadWorkflowDefinitions::MULTISTEP, $crawler->html());
//        $this->assertContains(LoadWorkflowDefinitions::WITH_START_STEP_SYSTEM, $crawler->html());
//    }

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
        $this->assertContains(LoadWorkflowDefinitions::MULTISTEP, $crawler->html());
    }

    public function testInfoAction()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_info', [
                '_widgetContainer' => 'dialog',
                'name' => LoadWorkflowDefinitions::MULTISTEP
            ]),
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
            $this->assertContains($workflow->getStepManager()->getStartStep()->getLabel(), $crawler->html());
        }
    }

    public function testDeactivateFormAction()
    {
        $this->workflowManager->deactivateWorkflow(LoadWorkflowDefinitions::MULTISTEP);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_activate_from_widget', [
                '_widgetContainer' => 'dialog',
                '_wid' => uniqid(),
                'name' => LoadWorkflowDefinitions::MULTISTEP,
            ]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $this->assertNotEmpty($crawler->html());
        $this->assertContains(LoadWorkflowDefinitions::MULTISTEP, $crawler->html());
        $this->assertContains('name="oro_workflow_replacement_select"', $crawler->html());
        $this->assertContains('Activate', $crawler->html());
        $this->assertContains('Cancel', $crawler->html());

        $form = $crawler->selectButton('Activate')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertNotEmpty($crawler->html());
    }
}
