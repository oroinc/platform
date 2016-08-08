<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolation
 */
class WorkflowControllerTest extends WebTestCase
{
    /** @var string */
    protected $entityClass = 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity';

    /** @var EntityManager */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions']);
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManagerForClass($this->entityClass);
    }

    public function testDeleteAction()
    {
        $workflowManager = $this->getWorkflowManager();
        $repository = $this->entityManager->getRepository('OroWorkflowBundle:WorkflowDefinition');

        $workflowOne = $repository->find(LoadWorkflowDefinitions::NO_START_STEP);
        $workflowTwo = $repository->find(LoadWorkflowDefinitions::WITH_START_STEP);
        $workflowOneName = $workflowOne->getName();
        $workflowTwoName = $workflowTwo->getName();

        $workflowManager->activateWorkflow($workflowOne->getName());
        $workflowManager->activateWorkflow($workflowTwo->getName());

        $entity = $this->createNewEntity();

        $this->assertActiveWorkflow($entity, $workflowOneName);

        $workflowManager->activateWorkflow($workflowTwo->getName());
        $this->assertActiveWorkflow($entity, $workflowTwoName);

        $activeWorkflowsCount = count($this->getWorkflowManager()->getApplicableWorkflows($entity));
        $this->assertGreaterThanOrEqual(2, $activeWorkflowsCount);

        $workflowManager->startWorkflow($workflowOneName, $entity, LoadWorkflowDefinitions::START_TRANSITION);
        $this->assertEntityWorkflowItem($entity, $workflowOneName);

        $workflowItem = $this->getWorkflowItem($entity, $workflowOneName);

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_workflow_delete',
                ['workflowItemId' => $workflowItem->getId()]
            )
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
        $this->assertEmpty($this->getWorkflowItem($entity, $workflowOneName));
        $this->assertNotEmpty($this->getWorkflowItem($entity, $workflowTwoName));
    }

    public function testDeactivateAndActivateActions()
    {
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);

        $testEntity = $this->createNewEntity();

        $this->assertActiveWorkflow($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $repositoryProcess = $this->getRepository('OroWorkflowBundle:ProcessDefinition');
        $processesBefore = $repositoryProcess->findAll();

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_workflow_deactivate',
                ['workflowDefinition' => LoadWorkflowDefinitions::WITH_START_STEP]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertActivationResult($result);
        $this->assertInactiveWorkflow($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $processes = $repositoryProcess->findAll();
        $this->assertCount(count($processesBefore) - 1, $processes);

        $this->assertEmpty($this->getWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP));

        $this->createWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);
        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_workflow_activate',
                ['workflowDefinition' => LoadWorkflowDefinitions::WITH_START_STEP]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertActivationResult($result);
        $this->assertActiveWorkflow($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $this->assertEmpty($this->getWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP));
    }

    /**
     * @return WorkflowAwareEntity
     */
    protected function createNewEntity()
    {
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test_' . uniqid('test', true));
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
        $workflowItem = $this->getWorkflowItem($entity, $workflowName);
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem', $workflowItem);
        $this->assertEquals($workflowName, $workflowItem->getWorkflowName());
    }

    /**
     * @param object $entity
     * @param string|null $workflowName
     */
    protected function assertActiveWorkflow($entity, $workflowName)
    {
        $activeWorkflowNames = $this->getActiveWorkflowNames($entity);

        $this->assertNotEmpty($activeWorkflowNames);
        $this->assertContains($workflowName, $activeWorkflowNames);
    }

    /**
     * @param object $entity
     * @param string|null $workflowName
     */
    protected function assertInactiveWorkflow($entity, $workflowName)
    {
        $this->assertNotContains($workflowName, $this->getActiveWorkflowNames($entity));
    }

    /**
     * @param string $entityClass
     *
     * @return ObjectManager
     */
    protected function getObjectManager($entityClass)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($entityClass);
    }

    /**
     * @param string $entityClass
     *
     * @return ObjectRepository
     */
    protected function getRepository($entityClass)
    {
        return $this->getObjectManager($entityClass)->getRepository($entityClass);
    }

    public function testGetValidWorkflowItem()
    {
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);

        $testEntity = $this->createNewEntity();

        $this->assertActiveWorkflow($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $workflowItem = $this->getWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_workflow_api_rest_workflow_get',
                ['workflowItemId' => $workflowItem->getId()]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($workflowItem->getId(), $result['workflowItem']['id']);
        $this->assertEquals($workflowItem->getWorkflowName(), $result['workflowItem']['workflow_name']);
        $this->assertEquals($workflowItem->getEntityId(), $result['workflowItem']['entity_id']);
        $this->assertEquals($workflowItem->getEntityClass(), $result['workflowItem']['entity_class']);
    }

    public function testStartWorkflow()
    {
        $testEntity = $this->createNewEntity();

        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::NO_START_STEP);
        $this->assertActiveWorkflow($testEntity, LoadWorkflowDefinitions::NO_START_STEP);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_workflow_api_rest_workflow_start',
                [
                    'workflowName' => LoadWorkflowDefinitions::NO_START_STEP,
                    'transitionName' => LoadWorkflowDefinitions::START_TRANSITION,
                    'entityId' => $testEntity->getId()
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::NO_START_STEP);

        $workflowItem = $this->getWorkflowItem($testEntity, LoadWorkflowDefinitions::NO_START_STEP);

        $this->assertEquals($workflowItem->getId(), $result['workflowItem']['id']);
        $this->assertEquals($workflowItem->getWorkflowName(), $result['workflowItem']['workflow_name']);
        $this->assertEquals($workflowItem->getEntityId(), $result['workflowItem']['entity_id']);
        $this->assertEquals($workflowItem->getEntityClass(), $result['workflowItem']['entity_class']);
    }

    public function testTransitAction()
    {
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::MULTISTEP);

        $testEntity = $this->createNewEntity();

        $this->assertActiveWorkflow($testEntity, LoadWorkflowDefinitions::MULTISTEP);

        $workflowItem = $this->getWorkflowItem($testEntity, LoadWorkflowDefinitions::MULTISTEP);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_workflow_api_rest_workflow_transit',
                [
                    'workflowItemId' => $workflowItem->getId(),
                    'transitionName' => LoadWorkflowDefinitions::MULTISTEP_START_TRANSITION,
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($workflowItem->getId(), $result['workflowItem']['id']);
        $this->assertEquals($workflowItem->getWorkflowName(), $result['workflowItem']['workflow_name']);
        $this->assertEquals($workflowItem->getEntityId(), $result['workflowItem']['entity_id']);
        $this->assertEquals($workflowItem->getEntityClass(), $result['workflowItem']['entity_class']);

        $workflowItemNew = $this->getRepository('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->find($workflowItem->getId());

        $this->assertNotEquals($workflowItem->getCurrentStep(), $workflowItemNew->getCurrentStep());
        $this->assertEquals('second_point', $workflowItemNew->getCurrentStep()->getName());
    }

    /**
     * @param string|WorkflowAwareEntity $entity
     * @param string|Workflow $workflowName
     *
     * @return null|WorkflowItem
     */
    protected function getWorkflowItem($entity, $workflowName)
    {
        return $this->getWorkflowManager()->getWorkflowItem($entity, $workflowName);
    }

    /**
     * @param string|WorkflowAwareEntity $entity
     * @param string|Workflow $workflowName
     *
     * @return null|WorkflowItem
     */
    protected function createWorkflowItem($entity, $workflowName)
    {
        $workflow = $this->getWorkflowManager()->getWorkflow($workflowName);
        $workflowItem = $workflow->createWorkflowItem($entity);

        $manager = $this->getObjectManager('OroWorkflowBundle:WorkflowItem');
        $manager->persist($workflowItem);
        $manager->flush();
    }

    /**
     * @param object $entity
     * @return array
     */
    protected function getActiveWorkflowNames($entity)
    {
        $activeWorkflows = $this->getWorkflowManager()->getApplicableWorkflows($entity);

        return array_map(
            function (Workflow $workflow) {
                return $workflow->getName();
            },
            $activeWorkflows
        );
    }
}
