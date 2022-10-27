<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model;

use Doctrine\ORM\Events;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;
use Oro\Bundle\WorkflowBundle\Tests\Functional\WorkflowTestCase;
use Oro\Component\Testing\Doctrine\StubEventListener;

/**
 * @dbIsolationPerTest
 */
class WorkflowManagerTest extends WorkflowTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    /**
     * @dataProvider massStartWorkflowDataProvider
     */
    public function testMassStartWorkflow(int $entitiesCount, int $postFlushCalls)
    {
        $startArgumentList = $this->getStartArgumentList($entitiesCount);

        $listenerMock = $this->createMock(StubEventListener::class);
        $listenerMock->expects($this->exactly($postFlushCalls))
            ->method('postFlush');

        $eventManager = $this->getEntityManager(WorkflowAwareEntity::class)->getEventManager();
        $eventManager->addEventListener(Events::postFlush, $listenerMock);

        $this->getSystemWorkflowManager()->massStartWorkflow($startArgumentList);

        $this->assertWorkflowItemsCount($entitiesCount, 'test_flow_mass_start');
        $this->assertWorkflowItemsCount($entitiesCount);
    }

    public function massStartWorkflowDataProvider(): array
    {
        return [
            'less than batch size' => [WorkflowManager::MASS_START_BATCH_SIZE - 1, 2],
            'batch size' => [WorkflowManager::MASS_START_BATCH_SIZE, 2],
            'greater than batch size' => [WorkflowManager::MASS_START_BATCH_SIZE + 1, 4]
        ];
    }

    public function testMassStartWorkflowWithRollback()
    {
        $startArgumentList = $this->getStartArgumentList();
        $transitionRecords = $this->getEntityManager(WorkflowTransitionRecord::class)
            ->getRepository(WorkflowTransitionRecord::class)->findAll();

        $listenerMock = $this->createMock(StubEventListener::class);

        $listenerMock->expects($this->once())
            ->method('onFlush')
            ->willThrowException(new \Exception('Message'));
        $listenerMock->expects($this->exactly(0))
            ->method('postFlush');

        $eventManager = $this->getEntityManager(WorkflowAwareEntity::class)->getEventManager();
        $eventManager->addEventListener(Events::onFlush, $listenerMock);
        $eventManager->addEventListener(Events::postFlush, $listenerMock);

        $this->getSystemWorkflowManager()->massStartWorkflow($startArgumentList);

        $this->assertWorkflowItemsCount(0);
        $this->assertWorkflowTransitionRecordCount(0 + count($transitionRecords));
    }

    private function getStartArgumentList(int $size = 10): array
    {
        $startArgumentList = [];

        for ($i = 0; $i < $size; $i++) {
            $startArgumentList[] = new WorkflowStartArguments(
                'test_flow_mass_start',
                $this->createWorkflowAwareEntity(false),
                [],
                'start_transition'
            );
        }
        $this->getEntityManager(WorkflowAwareEntity::class)->flush();

        $this->assertWorkflowItemsCount(0);

        $this->loadWorkflowFrom('/Tests/Functional/Model/DataFixtures/config/MassStart');

        return $startArgumentList;
    }
}
