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
    const CONFIG_PROVIDER_NAME = 'workflow';
    const CONFIG_KEY = 'active_workflows';

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

        $workflowManager->activateWorkflow($workflowOne);
        $this->assertActiveWorkflow($this->entityClass, $workflowOneName);

        $workflowManager->activateWorkflow($workflowTwo);
        $this->assertActiveWorkflow($this->entityClass, $workflowTwoName);

        $activeWorkflowsCount = count($this->getWorkflowManager()->getApplicableWorkflows($this->entityClass));
        $this->assertGreaterThanOrEqual(2, $activeWorkflowsCount);

        $entity = $this->createNewEntity();

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
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::WITH_START_STEP);

        $testEntity = $this->createNewEntity();
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
        $this->assertInactiveWorkflow($this->entityClass, LoadWorkflowDefinitions::WITH_START_STEP);

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
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::WITH_START_STEP);

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
     * @param string
     * @param string|null $workflowName
     */
    protected function assertActiveWorkflow($entityClass, $workflowName)
    {
        $activeWorkflowNames = $this->getActiveWorkflowNames($entityClass);

        $this->assertNotEmpty($activeWorkflowNames);
        $this->assertContains($workflowName, $activeWorkflowNames);
    }

    /**
     * @param string
     * @param string|null $workflowName
     */
    protected function assertInactiveWorkflow($entityClass, $workflowName)
    {
        $this->assertNotContains($workflowName, $this->getActiveWorkflowNames($entityClass));
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
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::WITH_START_STEP);

        $testEntity = $this->createNewEntity();
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

    public function testGetInvalidWorkflowItem()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_workflow_api_rest_workflow_get',
                ['workflowItemId' => 99999]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 302);
        $this->assertEmpty($result);
    }

    public function testStartWorkflow()
    {
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::NO_START_STEP);
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::NO_START_STEP);

        $testEntity = $this->createNewEntity();

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
        $this->assertActiveWorkflow($this->entityClass, LoadWorkflowDefinitions::MULTISTEP);

        $testEntity = $this->createNewEntity();

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

        $manager = $this->getObjectManager('OroWorkflowBundle:ProcessDefinition');
        $manager->persist($workflowItem);
        $manager->flush();
    }

    /**
     * @param string $entityClass
     * @return array
     */
    protected function getActiveWorkflowNames($entityClass)
    {
        $activeWorkflows = $this->getWorkflowManager()->getApplicableWorkflows($entityClass);

        return array_map(
            function (Workflow $workflow) {
                return $workflow->getName();
            },
            $activeWorkflows
        );
    }
}
