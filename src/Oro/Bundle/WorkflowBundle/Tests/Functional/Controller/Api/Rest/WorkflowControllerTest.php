<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

class WorkflowControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadWorkflowDefinitions::class]);
    }

    public function testDeleteAction(): void
    {
        $workflowManager = $this->getWorkflowManager();
        $repository = $this->getRepository(WorkflowDefinition::class);

        $workflowOne = $repository->find(LoadWorkflowDefinitions::NO_START_STEP);
        $workflowTwo = $repository->find(LoadWorkflowDefinitions::WITH_START_STEP);
        $workflowOneName = $workflowOne->getName();
        $workflowTwoName = $workflowTwo->getName();

        $workflowManager->activateWorkflow($workflowOne->getName());
        $workflowManager->activateWorkflow($workflowTwo->getName());

        $entity = $this->createNewEntity();

        $this->assertActiveWorkflow($entity, $workflowOneName);
        $this->assertActiveWorkflow($entity, $workflowTwoName);

        $this->assertCount(4, $this->getWorkflowManager()->getApplicableWorkflows($entity));

        $workflowManager->startWorkflow($workflowOneName, $entity, LoadWorkflowDefinitions::START_TRANSITION);
        $this->assertEntityWorkflowItem($entity, $workflowOneName);

        $workflowItem = $this->getWorkflowItem($entity, $workflowOneName);

        $this->client->jsonRequest(
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

    public function testDeactivateAndActivateActions(): void
    {
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);

        $testEntity = $this->createNewEntity();

        $this->assertActiveWorkflow($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $repositoryProcess = $this->getRepository(ProcessDefinition::class);
        $processesBefore = $repositoryProcess->findAll();

        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_api_workflow_deactivate',
                ['workflowDefinition' => LoadWorkflowDefinitions::WITH_START_STEP]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertActivationResult($result);
        $this->assertInactiveWorkflow($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $processes = $repositoryProcess->findAll();
        $this->assertCount(count($processesBefore), $processes);

        $this->assertEmpty($this->getWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP));

        $this->createWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);
        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $this->client->jsonRequest(
            'POST',
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

    private function createNewEntity(): WorkflowAwareEntity
    {
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test_' . uniqid('test', true));

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $em->persist($testEntity);
        $em->flush($testEntity);

        return $testEntity;
    }

    private function getWorkflowManager(): WorkflowManager
    {
        return $this->client->getContainer()->get('oro_workflow.manager.system');
    }

    private function assertActivationResult(array $result): void
    {
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['successful']);
        $this->assertNotEmpty($result['message']);
    }

    private function assertEntityWorkflowItem(WorkflowAwareEntity $entity, ?string $workflowName): void
    {
        $workflowItem = $this->getWorkflowItem($entity, $workflowName);
        $this->assertInstanceOf(WorkflowItem::class, $workflowItem);
        $this->assertEquals($workflowName, $workflowItem->getWorkflowName());
    }

    private function assertActiveWorkflow(object $entity, ?string $workflowName): void
    {
        $activeWorkflowNames = $this->getActiveWorkflowNames($entity);

        $this->assertNotEmpty($activeWorkflowNames);
        $this->assertContains($workflowName, $activeWorkflowNames);
    }

    private function assertInactiveWorkflow(object $entity, ?string $workflowName): void
    {
        $this->assertNotContains($workflowName, $this->getActiveWorkflowNames($entity));
    }

    private function getRepository(string $entityClass): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository($entityClass);
    }

    public function testGetValidWorkflowItem(): void
    {
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::WITH_START_STEP);

        $testEntity = $this->createNewEntity();

        $this->assertActiveWorkflow($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $this->assertEntityWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $workflowItem = $this->getWorkflowItem($testEntity, LoadWorkflowDefinitions::WITH_START_STEP);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_workflow_get',
                ['workflowItemId' => $workflowItem->getId()]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                'workflowItem' => [
                    'id' => $workflowItem->getId(),
                    'workflow_name' => $workflowItem->getWorkflowName(),
                    'entity_id' => $workflowItem->getEntityId(),
                    'entity_class' => $workflowItem->getEntityClass(),
                    'result' => null,
                ]
            ],
            $result
        );
    }

    public function testStartWorkflow(): void
    {
        $testEntity = $this->createNewEntity();

        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::NO_START_STEP);
        $this->assertActiveWorkflow($testEntity, LoadWorkflowDefinitions::NO_START_STEP);

        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_api_workflow_start',
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

        $this->assertEquals(
            [
                'workflowItem' => [
                    'id' => $workflowItem->getId(),
                    'workflow_name' => $workflowItem->getWorkflowName(),
                    'entity_id' => $workflowItem->getEntityId(),
                    'entity_class' => $workflowItem->getEntityClass(),
                    'result' => null,
                ]
            ],
            $result
        );
    }

    public function testStartWorkflowWithInvalidCondition(): void
    {
        $testEntity = $this->createNewEntity();

        $this->getWorkflowManager()->activateWorkflow('test_flow_with_condition');
        $this->assertActiveWorkflow($testEntity, 'test_flow_with_condition');

        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_api_workflow_start',
                [
                    'workflowName' => 'test_flow_with_condition',
                    'transitionName' => 'start_transition',
                    'entityId' => $testEntity->getId()
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 403);
        $this->assertEquals(['message' => 'custom.test.message'], $result);

        $this->assertNull($this->getWorkflowItem($testEntity, 'test_flow_with_condition'));
    }

    public function testTransitAction(): void
    {
        $this->getWorkflowManager()->activateWorkflow(LoadWorkflowDefinitions::MULTISTEP);

        $testEntity = $this->createNewEntity();

        $this->assertActiveWorkflow($testEntity, LoadWorkflowDefinitions::MULTISTEP);

        $workflowItem = $this->getWorkflowItem($testEntity, LoadWorkflowDefinitions::MULTISTEP);

        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_api_workflow_transit',
                [
                    'workflowItemId' => $workflowItem->getId(),
                    'transitionName' => LoadWorkflowDefinitions::MULTISTEP_START_TRANSITION,
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                'workflowItem' => [
                    'id' => $workflowItem->getId(),
                    'workflow_name' => $workflowItem->getWorkflowName(),
                    'entity_id' => $workflowItem->getEntityId(),
                    'entity_class' => $workflowItem->getEntityClass(),
                    'result' => null,
                ]
            ],
            $result
        );

        $workflowItemNew = $this->getRepository(WorkflowItem::class)
            ->find($workflowItem->getId());

        $this->assertEquals('second_point', $workflowItemNew->getCurrentStep()->getName());

        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_api_workflow_transit',
                [
                    'workflowItemId' => $workflowItem->getId(),
                    'transitionName' => 'second_point_transition',
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                'workflowItem' => [
                    'id' => $workflowItem->getId(),
                    'workflow_name' => LoadWorkflowDefinitions::MULTISTEP,
                    'entity_id' => $workflowItem->getEntityId(),
                    'entity_class' => $workflowItem->getEntityClass(),
                    'result' => null,
                ]
            ],
            $result
        );
    }

    public function testTransitActionWithInvalidCondition(): void
    {
        $this->getWorkflowManager()->activateWorkflow('test_flow_transition_with_condition');

        $testEntity = $this->createNewEntity();

        $this->assertActiveWorkflow($testEntity, 'test_flow_transition_with_condition');

        $workflowItem = $this->getWorkflowItem($testEntity, 'test_flow_transition_with_condition');

        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_api_workflow_transit',
                [
                    'workflowItemId' => $workflowItem->getId(),
                    'transitionName' => 'starting_point_transition',
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                'workflowItem' => [
                    'id' => $workflowItem->getId(),
                    'workflow_name' => $workflowItem->getWorkflowName(),
                    'entity_id' => $workflowItem->getEntityId(),
                    'entity_class' => $workflowItem->getEntityClass(),
                    'result' => null,
                ]
            ],
            $result
        );

        $workflowItemNew = $this->getRepository(WorkflowItem::class)
            ->find($workflowItem->getId());

        $this->assertEquals('second_point', $workflowItemNew->getCurrentStep()->getName());

        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_api_workflow_transit',
                [
                    'workflowItemId' => $workflowItem->getId(),
                    'transitionName' => 'second_point_transition',
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 403);

        $this->assertEquals(['message' => 'custom.test.message'], $result);
    }

    /**
     * @dataProvider startWorkflowDataProvider
     */
    public function testStartWorkflowFromNonRelatedEntity(
        string $routeName,
        string $entityClass,
        string $transitionName
    ): void {
        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_api_workflow_start',
                [
                    'workflowName' => LoadWorkflowDefinitions::WITH_INIT_OPTION,
                    'transitionName' => $transitionName,
                    'entityClass' => $entityClass,
                    'route' => $routeName
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $workflowItem = $this->getRepository(WorkflowItem::class)
            ->find($result['workflowItem']['id']);

        $this->assertEquals(
            [
                'workflowItem' => [
                    'id' => $workflowItem->getId(),
                    'workflow_name' => $workflowItem->getWorkflowName(),
                    'entity_id' => $workflowItem->getEntityId(),
                    'entity_class' => $workflowItem->getEntityClass(),
                    'result' => null,
                ]
            ],
            $result
        );

        /** @var ButtonSearchContext $initContext */
        $initContext = $workflowItem->getData()->get('init_context');

        $this->assertInstanceOf(ButtonSearchContext::class, $initContext);
        $this->assertEquals($initContext->getEntityClass(), $entityClass);
        $this->assertEquals($initContext->getRouteName(), $routeName);
    }

    public function startWorkflowDataProvider(): array
    {
        return [
            'startFromNonRelatedEntity' => [
                'route' => 'route1',
                'entityClass' => 'class1',
                'transitionName' => LoadWorkflowDefinitions::START_FROM_ENTITY_TRANSITION,
            ],
            'startFromRoute' => [
                'route' => 'route2',
                'entityClass' => 'class2',
                'transitionName' => LoadWorkflowDefinitions::START_FROM_ROUTE_TRANSITION,
            ]
        ];
    }

    private function getWorkflowItem(WorkflowAwareEntity $entity, string $workflowName): ?WorkflowItem
    {
        return $this->getWorkflowManager()->getWorkflowItem($entity, $workflowName);
    }

    private function createWorkflowItem(WorkflowAwareEntity $entity, string $workflowName): void
    {
        $workflow = $this->getWorkflowManager()->getWorkflow($workflowName);
        $workflowItem = $workflow->createWorkflowItem($entity);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowItem::class);
        $em->persist($workflowItem);
        $em->flush();
    }

    private function getActiveWorkflowNames(object $entity): array
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
