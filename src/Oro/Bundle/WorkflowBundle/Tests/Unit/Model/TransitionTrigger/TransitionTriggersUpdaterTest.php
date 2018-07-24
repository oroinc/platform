<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Cron\TransitionTriggerCronScheduler;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggersUpdateDecider;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggersUpdater;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggersBag;

class TransitionTriggersUpdaterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionTriggersUpdater */
    private $updater;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /**@var TransitionTriggersUpdateDecider|\PHPUnit\Framework\MockObject\MockObject */
    private $updateDecider;

    /** @var TransitionTriggerCronScheduler|\PHPUnit\Framework\MockObject\MockObject */
    private $cronScheduler;

    /** @var EventTriggerCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->updateDecider = $this->createMock(TransitionTriggersUpdateDecider::class);
        $this->cronScheduler = $this->getMockBuilder(TransitionTriggerCronScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockBuilder(EventTriggerCache::class)->disableOriginalConstructor()->getMock();

        $this->updater = new TransitionTriggersUpdater(
            $this->doctrineHelper,
            $this->updateDecider,
            $this->cronScheduler,
            $this->cache
        );
    }

    public function testUpdateTriggers()
    {
        $definition = (new WorkflowDefinition())->setName('workflow');
        $newCronTrigger1 = (new TransitionCronTrigger())->setCron('1 * * * *');
        $newEventTrigger2 = (new TransitionEventTrigger())->setEvent('create');

        $triggersBag = new TriggersBag($definition, [$newCronTrigger1, $newEventTrigger2]);

        $storedCronTrigger1 = (new TransitionCronTrigger())->setCron('2 * * * *');
        $storedEventTrigger2 = (new TransitionEventTrigger())->setEvent('update');

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(static::once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$storedCronTrigger1, $storedEventTrigger2]);

        $cronTriggerRepository = $this->createMock(EntityRepository::class);
        $cronTriggerRepository
            ->expects(static::once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$newCronTrigger1]);

        $this->doctrineHelper->expects(static::any())
            ->method('getEntityRepositoryForClass')
            ->withConsecutive([BaseTransitionTrigger::class], [TransitionCronTrigger::class])
            ->willReturnOnConsecutiveCalls($repository, $cronTriggerRepository);

        $this->updateDecider
            ->expects(static::once())
            ->method('decide')
            ->with(
                [$storedCronTrigger1, $storedEventTrigger2],
                $triggersBag->getTriggers()
            )
            ->willReturn([
                [$newCronTrigger1, $newEventTrigger2],
                [$storedCronTrigger1, $storedEventTrigger2]
            ]);

        $em = $this->emRetrieval();

        $em->expects($this->at(0))->method('remove')->with($storedCronTrigger1);
        $this->cronScheduler->expects($this->once())->method('removeSchedule')->with($storedCronTrigger1);
        $em->expects($this->at(1))->method('remove')->with($storedEventTrigger2);
        $em->expects($this->at(2))->method('persist')->with($newCronTrigger1);
        $this->cronScheduler->expects($this->once())->method('addSchedule')->with($newCronTrigger1);
        $em->expects($this->at(3))->method('persist')->with($newEventTrigger2);

        $em->expects($this->once())->method('flush');
        $this->cronScheduler->expects(static::exactly(2))->method('flush');
        $this->cache->expects($this->once())->method('build');

        $this->updater->updateTriggers($triggersBag);
    }

    public function testUpdateTriggersNoActionsForTriggersButUpdateCronSchedule()
    {
        $definition = (new WorkflowDefinition())->setName('workflow');
        $newTrigger1 = (new TransitionCronTrigger())->setCron('1 * * * *');
        $newTrigger2 = (new TransitionEventTrigger())->setEvent('create');

        $triggersBag = new TriggersBag($definition, [$newTrigger1, $newTrigger2]);

        $storedTrigger1 = (new TransitionCronTrigger())->setCron('2 * * * *');
        $storedTrigger2 = (new TransitionEventTrigger())->setEvent('update');

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(static::once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$storedTrigger1, $storedTrigger2]);

        $cronTriggerRepository = $this->createMock(EntityRepository::class);
        $cronTriggerRepository
            ->expects(static::once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$storedTrigger1]);

        $this->doctrineHelper->expects(static::any())
            ->method('getEntityRepositoryForClass')
            ->withConsecutive([BaseTransitionTrigger::class], [TransitionCronTrigger::class])
            ->willReturnOnConsecutiveCalls($repository, $cronTriggerRepository);

        $this->updateDecider->expects($this->once())
            ->method('decide')
            ->with([$storedTrigger1, $storedTrigger2], $triggersBag->getTriggers())
            ->willReturn([[], []]); //all abstain by decider

        $this->cronScheduler
            ->expects(static::once())
            ->method('addSchedule')
            ->with($storedTrigger1);

        $this->cronScheduler
            ->expects(static::once())
            ->method('flush');

        //no em actions
        $this->cache->expects($this->never())->method('build');

        $this->updater->updateTriggers($triggersBag);
    }

    public function testRemoveTriggers()
    {
        $definition = (new WorkflowDefinition())->setName('workflow');

        $repository = $this->repositoryRetrieval();

        $trigger = new TransitionCronTrigger();
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['workflowDefinition' => $definition->getName()])
            ->willReturn([$trigger]);

        $em = $this->emRetrieval();
        $em->expects($this->once())->method('remove')->with($trigger);
        $this->cronScheduler->expects($this->once())->method('removeSchedule')->with($trigger);
        $em->expects($this->once())->method('flush');
        $this->cronScheduler->expects($this->once())->method('flush');
        $this->cache->expects($this->once())->method('build');

        $this->updater->removeTriggers($definition);
    }

    public function testRemoveTriggersDry()
    {
        $definition = (new WorkflowDefinition())->setName('workflow');

        $repository = $this->repositoryRetrieval();

        $repository->expects($this->once())
            ->method('findBy')
            ->with(['workflowDefinition' => $definition->getName()])
            ->willReturn([]);

        //no em actions
        $this->cache->expects($this->never())->method('build');

        $this->updater->removeTriggers($definition);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityManager
     */
    public function emRetrieval()
    {
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(BaseTransitionTrigger::class)->willReturn($em);

        return $em;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityRepository
     */
    public function repositoryRetrieval()
    {
        $repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(BaseTransitionTrigger::class)
            ->willReturn($repository);

        return $repository;
    }
}
