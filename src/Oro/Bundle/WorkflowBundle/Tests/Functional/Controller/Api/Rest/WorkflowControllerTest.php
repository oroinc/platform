<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolation
 */
class WorkflowControllerTest extends WebTestCase
{
    /**
     * @var string
     */
    protected $entityClass = 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions']);
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManagerForClass($this->entityClass);
    }

    /**
     * @dataProvider deleteDataProvider
     */
    public function testDeleteAction($finalDefinitionHasStartStep)
    {
        $workflowManager = $this->getWorkflowManager();
        $repository      = $this->entityManager->getRepository('OroWorkflowBundle:WorkflowDefinition');

        $workflowDefinitionNoStartStep = $repository->find(LoadWorkflowDefinitions::NO_START_STEP);
        $workflowDefinitionStartStep   = $repository->find(LoadWorkflowDefinitions::WITH_START_STEP);

        $workflowFrom = $finalDefinitionHasStartStep ? $workflowDefinitionNoStartStep : $workflowDefinitionStartStep;
        $workflowTo   = $finalDefinitionHasStartStep ? $workflowDefinitionStartStep : $workflowDefinitionNoStartStep;
        $workflowFromName = $workflowFrom->getName();
        $workflowToName   = $workflowTo->getName();

        // activating workflow definition
        $workflowManager->activateWorkflow($workflowFrom);
        $this->assertActiveWorkflow($this->entityClass, $workflowFromName);

        // create new test entity
        $entity = $this->createNewEntity();
        if ($finalDefinitionHasStartStep) {
            $workflowManager->startWorkflow($workflowFromName, $entity, LoadWorkflowDefinitions::START_TRANSITION);
        }
        $this->assertEntityWorkflowItem($entity, $workflowFromName);

        // activating other workflow definition
        $workflowManager->activateWorkflow($workflowTo);
        $this->assertActiveWorkflow($this->entityClass, $workflowToName);

        // assert the current entity still has relation to the not active workflow definition
        $entity = $this->refreshEntity($entity);
        $this->assertEntityWorkflowItem($entity, $workflowFromName);

        // performing delete workflow item using API
        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_workflow_delete',
                ['workflowItemId' => $entity->getWorkflowItem()->getId()]
            )
        );

        // check is deleting workflow item has been performed successfully
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $entity = $this->refreshEntity($entity);
        if ($finalDefinitionHasStartStep) {
            $this->assertEntityWorkflowItem($entity, $workflowToName);
        } else {
            $this->assertEntityWorkflowItem($entity, null);
        }
    }

    /**
     * @param WorkflowAwareEntity $entity
     *
     * @return WorkflowAwareEntity
     */
    protected function refreshEntity(WorkflowAwareEntity $entity)
    {
        $entity = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity')
            ->findOneBy([
                'id' => $entity->getId()
            ]);

        return $entity;
    }

    /**
     * @return array
     */
    public function deleteDataProvider()
    {
        return [
            'final definition with start step' => [true],
            'final definition without start step' => [false],
        ];
    }

    public function testDeactivateAndActivateActions()
    {
        // set and assert active workflow
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::WITH_START_STEP);

        // create and assert test entity
        $testEntity = $this->createNewEntity();
        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $repositoryProcess = $this->getRepository('OroWorkflowBundle:ProcessDefinition');
        $processesBefore = $repositoryProcess->findAll();

        // deactivate workflow for entity
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_workflow_deactivate',
                ['entityClass' => $this->entityClass]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertActivationResult($result);
        $this->assertActiveWorkflow($this->entityClass, null);

        $processes = $repositoryProcess->findAll();
        $this->assertCount(count($processesBefore) - 1, $processes);

        // assert that entity still has relation to workflow
        $testEntity = $this->refreshEntity($testEntity);
        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        // activate other workflow through API
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_workflow_activate',
                ['workflowDefinition' => LoadWorkflowDefinitions::NO_START_STEP]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertActivationResult($result);
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::NO_START_STEP);

        // assert that entity workflow item was reset
        $testEntity = $this->refreshEntity($testEntity);
        $this->assertEntityWorkflowItem($testEntity, null);
    }

    /**
     * @return WorkflowAwareEntity
     */
    protected function createNewEntity()
    {
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test_' . uniqid());
        $this->entityManager->persist($testEntity);
        $this->entityManager->flush($testEntity);

        return $testEntity;
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

    /**
     * @param string $entityClass
     *
     * @return ObjectRepository
     */
    protected function getRepository($entityClass)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
    }
}
