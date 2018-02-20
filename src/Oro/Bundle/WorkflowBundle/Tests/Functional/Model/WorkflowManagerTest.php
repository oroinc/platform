<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
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
    /** @var EntityManager */
    protected $entityManger;

    /** @var WorkflowManager */
    protected $systemWorkflowManager;

    protected function setUp()
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->entityManger = $this->getEntityManager(WorkflowAwareEntity::class);
        $this->systemWorkflowManager = self::getSystemWorkflowManager();
    }

    /**
     * @param int $entitiesCount
     * @param int $postFlushCalls
     *
     * @dataProvider massStartWorkflowDataProvider
     */
    public function testMassStartWorkflow($entitiesCount, $postFlushCalls)
    {
        $startArgumentList = $this->getStartArgumentList($entitiesCount);

        $listenerMock = $this->createMock(StubEventListener::class);
        $listenerMock->expects($this->exactly($postFlushCalls))->method('postFlush');

        /** @var EventManager $eventManager */
        $eventManager = $this->entityManger->getEventManager();
        $eventManager->addEventListener(Events::postFlush, $listenerMock);

        $this->getSystemWorkflowManager()->massStartWorkflow($startArgumentList);

        $this->assertWorkflowItemsCount($entitiesCount, 'test_flow_mass_start');
        $this->assertWorkflowItemsCount($entitiesCount);
    }

    /**
     * @return \Generator
     */
    public function massStartWorkflowDataProvider()
    {
        yield 'less than batch size' => [WorkflowManager::MASS_START_BATCH_SIZE - 1, 2];
        yield 'batch size' => [WorkflowManager::MASS_START_BATCH_SIZE, 2];
        yield 'greater than batch size' => [WorkflowManager::MASS_START_BATCH_SIZE + 1, 4];
    }

    public function testMassStartWorkflowWithRollback()
    {
        $startArgumentList = $this->getStartArgumentList();
        $transitionRecords = $this->getEntityManager(WorkflowTransitionRecord::class)
            ->getRepository(WorkflowTransitionRecord::class)->findAll();

        $listenerMock = $this->createMock(StubEventListener::class);

        $listenerMock->expects($this->exactly(1))->method('onFlush')->willThrowException(new \Exception('Message'));
        $listenerMock->expects($this->exactly(0))->method('postFlush');

        /** @var EventManager $eventManager */
        $eventManager = $this->entityManger->getEventManager();
        $eventManager->addEventListener(Events::onFlush, $listenerMock);
        $eventManager->addEventListener(Events::postFlush, $listenerMock);

        $this->getSystemWorkflowManager()->massStartWorkflow($startArgumentList);

        $this->assertWorkflowItemsCount(0);
        $this->assertWorkflowTransitionRecordCount(0 + count($transitionRecords));
    }

    /**
     * @param int $size
     *
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function getStartArgumentList($size = 10)
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
        $this->entityManger->flush();

        $this->assertWorkflowItemsCount(0);

        self::loadWorkflowFrom('/Tests/Functional/Model/DataFixtures/config/MassStart');

        return $startArgumentList;
    }
}
