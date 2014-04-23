<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @db_isolation
 * @db_reindex
 */
class WorkflowControllerTest extends WebTestCase
{
    static protected $fixturesLoaded = false;

    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        parent::setUp();

        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
        if (!self::$fixturesLoaded) {
            $prev = '..' . DIRECTORY_SEPARATOR;
            $path = __DIR__ . DIRECTORY_SEPARATOR . $prev . $prev . $prev . 'DataFixtures';
            $this->client->appendFixtures($path, array('LoadWorkflowDefinitions'));
            self::$fixturesLoaded = true;
        }
    }

    public function testDeleteAction()
    {
        $manager = $this->client->getContainer()->get('doctrine')->getManager();
        $entity = $manager->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity')->findAll();
        $this->assertNotEmpty($entity);
    }

    public function testDeactivateAndActivateActions()
    {
        $entityClass = 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity';
        $entityManager = $this->client->getContainer()->get('doctrine')->getManagerForClass($entityClass);

        // set and assert active workflow
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);
        $this->assertActiveWorkflow($entityClass, LoadWorkflowDefinitions::WITH_START_STEP);

        // create and assert test entity
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test');
        $entityManager->persist($testEntity);
        $entityManager->flush($testEntity);
        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        // deactivate workflow for entity
        $this->client->request(
            'GET',
            $this->client->generate(
                'oro_workflow_api_rest_workflow_deactivate',
                array('entityClass' => $entityClass)
            )
        );
        $result = $this->client->getResponse();
        $result = ToolsApi::jsonToArray($result->getContent());
        $this->assertActivationResult($result);
        $this->assertActiveWorkflow($entityClass, null);

        // assert that entity still has relation to workflow
        $entityManager->refresh($testEntity);
        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        // activate other workflow through API
        $this->client->request(
            'GET',
            $this->client->generate(
                'oro_workflow_api_rest_workflow_activate',
                array('workflowDefinition' => LoadWorkflowDefinitions::NO_START_STEP)
            )
        );
        $result = $this->client->getResponse();
        $result = ToolsApi::jsonToArray($result->getContent());
        $this->assertActivationResult($result);
        $this->assertActiveWorkflow($entityClass, LoadWorkflowDefinitions::NO_START_STEP);

        // assert that entity workflow item was reset
        $entityManager->refresh($testEntity);
        $this->assertEntityWorkflowItem($testEntity, null);
    }

    /**
     * @return WorkflowManager
     */
    protected function getWorkflowManager()
    {
        return $this->client->getContainer()->get('oro_workflow.manager');
    }

    /**
     * @param array $result
     */
    protected function assertActivationResult($result)
    {
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['successful']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * @param WorkflowAwareEntity $entity
     * @param string|null $workflowName
     */
    protected function assertEntityWorkflowItem(WorkflowAwareEntity $entity, $workflowName)
    {
        if ($workflowName) {
            $this->assertNotEmpty($entity->getWorkflowItem());
            $this->assertEquals($workflowName, $entity->getWorkflowItem()->getWorkflowName());
        } else {
            $this->assertEmpty($entity->getWorkflowItem());
        }
    }

    /**
     * @param string
     * @param string|null $workflowName
     */
    protected function assertActiveWorkflow($entityClass, $workflowName)
    {
        if ($workflowName) {
            $activeWorkflow = $this->getWorkflowManager()->getApplicableWorkflowByEntityClass($entityClass);
            $this->assertNotEmpty($activeWorkflow);
            $this->assertEquals($workflowName, $activeWorkflow->getName());
        } else {
            $this->assertNull($this->getWorkflowManager()->getApplicableWorkflowByEntityClass($entityClass));
        }
    }
}
