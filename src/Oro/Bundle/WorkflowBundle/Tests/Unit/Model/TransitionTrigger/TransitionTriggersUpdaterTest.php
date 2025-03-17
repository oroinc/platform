<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Doctrine\ORM\EntityManagerInterface;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransitionTriggersUpdaterTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private TransitionTriggersUpdateDecider&MockObject $updateDecider;
    private TransitionTriggerCronScheduler&MockObject $cronScheduler;
    private EventTriggerCache&MockObject $cache;
    private TransitionTriggersUpdater $updater;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->updateDecider = $this->createMock(TransitionTriggersUpdateDecider::class);
        $this->cronScheduler = $this->createMock(TransitionTriggerCronScheduler::class);
        $this->cache = $this->createMock(EventTriggerCache::class);

        $this->updater = new TransitionTriggersUpdater(
            $this->doctrineHelper,
            $this->updateDecider,
            $this->cronScheduler,
            $this->cache
        );
    }

    public function testUpdateTriggers(): void
    {
        $definition = (new WorkflowDefinition())->setName('workflow');
        $newCronTrigger1 = (new TransitionCronTrigger())->setCron('1 * * * *');
        $newEventTrigger2 = (new TransitionEventTrigger())->setEvent('create');

        $triggersBag = new TriggersBag($definition, [$newCronTrigger1, $newEventTrigger2]);

        $storedCronTrigger1 = (new TransitionCronTrigger())->setCron('2 * * * *');
        $storedEventTrigger2 = (new TransitionEventTrigger())->setEvent('update');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$storedCronTrigger1, $storedEventTrigger2]);

        $cronTriggerRepository = $this->createMock(EntityRepository::class);
        $cronTriggerRepository->expects(self::once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$newCronTrigger1]);

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepositoryForClass')
            ->withConsecutive([BaseTransitionTrigger::class], [TransitionCronTrigger::class])
            ->willReturnOnConsecutiveCalls($repository, $cronTriggerRepository);

        $this->updateDecider->expects(self::once())
            ->method('decide')
            ->with(
                [$storedCronTrigger1, $storedEventTrigger2],
                $triggersBag->getTriggers()
            )
            ->willReturn([
                [$newCronTrigger1, $newEventTrigger2],
                [$storedCronTrigger1, $storedEventTrigger2]
            ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with(BaseTransitionTrigger::class)
            ->willReturn($em);

        $em->expects(self::exactly(2))
            ->method('persist')
            ->withConsecutive([$newCronTrigger1], [$newEventTrigger2]);
        $em->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive([$storedCronTrigger1], [$storedEventTrigger2]);
        $this->cronScheduler->expects(self::once())
            ->method('removeSchedule')
            ->with($storedCronTrigger1);
        $this->cronScheduler->expects(self::once())
            ->method('addSchedule')
            ->with($newCronTrigger1);

        $em->expects(self::once())
            ->method('flush');
        $this->cronScheduler->expects(self::exactly(2))
            ->method('flush');
        $this->cache->expects(self::once())
            ->method('build');

        $this->updater->updateTriggers($triggersBag);
    }

    public function testUpdateTriggersNoActionsForTriggersButUpdateCronSchedule(): void
    {
        $definition = (new WorkflowDefinition())->setName('workflow');
        $newTrigger1 = (new TransitionCronTrigger())->setCron('1 * * * *');
        $newTrigger2 = (new TransitionEventTrigger())->setEvent('create');

        $triggersBag = new TriggersBag($definition, [$newTrigger1, $newTrigger2]);

        $storedTrigger1 = (new TransitionCronTrigger())->setCron('2 * * * *');
        $storedTrigger2 = (new TransitionEventTrigger())->setEvent('update');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$storedTrigger1, $storedTrigger2]);

        $cronTriggerRepository = $this->createMock(EntityRepository::class);
        $cronTriggerRepository->expects(self::once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$storedTrigger1]);

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepositoryForClass')
            ->withConsecutive([BaseTransitionTrigger::class], [TransitionCronTrigger::class])
            ->willReturnOnConsecutiveCalls($repository, $cronTriggerRepository);

        $this->updateDecider->expects(self::once())
            ->method('decide')
            ->with([$storedTrigger1, $storedTrigger2], $triggersBag->getTriggers())
            ->willReturn([[], []]); //all abstain by decider

        $this->cronScheduler->expects(self::once())
            ->method('addSchedule')
            ->with($storedTrigger1);

        $this->cronScheduler->expects(self::once())
            ->method('flush');

        $this->cache->expects(self::never())
            ->method('build');

        $this->updater->updateTriggers($triggersBag);
    }

    public function testRemoveTriggers(): void
    {
        $definition = (new WorkflowDefinition())->setName('workflow');

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepositoryForClass')
            ->with(BaseTransitionTrigger::class)
            ->willReturn($repository);

        $trigger = new TransitionCronTrigger();
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['workflowDefinition' => $definition->getName()])
            ->willReturn([$trigger]);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with(BaseTransitionTrigger::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('remove')
            ->with($trigger);
        $this->cronScheduler->expects(self::once())
            ->method('removeSchedule')
            ->with($trigger);
        $em->expects(self::once())
            ->method('flush');
        $this->cronScheduler->expects(self::once())
            ->method('flush');
        $this->cache->expects(self::once())
            ->method('build');

        $this->updater->removeTriggers($definition);
    }

    public function testRemoveTriggersDry(): void
    {
        $definition = (new WorkflowDefinition())->setName('workflow');

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepositoryForClass')
            ->with(BaseTransitionTrigger::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findBy')
            ->with(['workflowDefinition' => $definition->getName()])
            ->willReturn([]);

        $this->cache->expects(self::never())
            ->method('build');

        $this->updater->removeTriggers($definition);
    }
}
