<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggersUpdateDecider;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggersUpdater;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggersBag;

class TransitionTriggersUpdaterTest extends \PHPUnit_Framework_TestCase
{
    /** @var TransitionTriggersUpdater */
    private $updater;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /**@var TransitionTriggersUpdateDecider|\PHPUnit_Framework_MockObject_MockObject */
    private $updateDecider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->updateDecider = $this->getMock(TransitionTriggersUpdateDecider::class);
        $this->updater = new TransitionTriggersUpdater($this->doctrineHelper, $this->updateDecider);
    }

    public function testUpdateTriggers()
    {
        $definition = (new WorkflowDefinition())->setName('workflow');
        $newTrigger1 = (new TransitionCronTrigger())->setCron('1 * * * *');
        $newTrigger2 = (new TransitionEventTrigger())->setEvent('create');

        $triggersBag = new TriggersBag($definition, [$newTrigger1, $newTrigger2]);

        $storedTrigger1 = (new TransitionCronTrigger())->setCron('2 * * * *');
        $storedTrigger2 = (new TransitionEventTrigger())->setEvent('update');

        $repository = $this->repositoryRetrieval();
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$storedTrigger1, $storedTrigger2]);

        $this->updateDecider->expects($this->once())->method('decide')->with(
            [$storedTrigger1, $storedTrigger2],
            $triggersBag->getTriggers()
        )->willReturn([[$newTrigger1, $newTrigger2], [$storedTrigger1, $storedTrigger2]]);

        $em = $this->emRetrieval();

        $em->expects($this->at(0))->method('remove')->with($storedTrigger1);
        $em->expects($this->at(1))->method('remove')->with($storedTrigger2);
        $em->expects($this->at(2))->method('persist')->with($newTrigger1);
        $em->expects($this->at(3))->method('persist')->with($newTrigger2);

        $em->expects($this->once())->method('flush');

        $this->updater->updateTriggers($triggersBag);
    }

    public function testUpdateTriggersNoActions()
    {
        $definition = (new WorkflowDefinition())->setName('workflow');
        $newTrigger1 = (new TransitionCronTrigger())->setCron('1 * * * *');
        $newTrigger2 = (new TransitionEventTrigger())->setEvent('create');

        $triggersBag = new TriggersBag($definition, [$newTrigger1, $newTrigger2]);

        $storedTrigger1 = (new TransitionCronTrigger())->setCron('2 * * * *');
        $storedTrigger2 = (new TransitionEventTrigger())->setEvent('update');

        $repository = $this->repositoryRetrieval();
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['workflowDefinition' => 'workflow'])
            ->willReturn([$storedTrigger1, $storedTrigger2]);

        $this->updateDecider->expects($this->once())
            ->method('decide')
            ->with([$storedTrigger1, $storedTrigger2], $triggersBag->getTriggers())
            ->willReturn([[], []]); //all abstain by decider

        //no em actions

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
        $em->expects($this->once())->method('flush');

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

        $this->updater->removeTriggers($definition);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    public function emRetrieval()
    {
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(AbstractTransitionTrigger::class)->willReturn($em);

        return $em;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityRepository
     */
    public function repositoryRetrieval()
    {
        $repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(AbstractTransitionTrigger::class)
            ->willReturn($repository);

        return $repository;
    }
}
