<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggerCronVerifier;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

use Oro\Component\Testing\Unit\EntityTrait;

class TransitionTriggerCronVerifierTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS = 'stdClass';

    /** @var WorkflowAssembler|\PHPUnit_Framework_MockObject_MockObject */
    private $workflowAssembler;

    /** @var WorkflowItemRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $workflowItemRepository;

    /** @var ExpressionVerifierInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $cronVerifier;

    /** @var ExpressionVerifierInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $filterVerifier;

    /** @var TransitionTriggerCronVerifier */
    private $verifier;

    protected function setUp()
    {
        $this->workflowAssembler = $this->getMockBuilder(WorkflowAssembler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowItemRepository = $this->getMockBuilder(WorkflowItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(WorkflowItem::class)
            ->willReturn($this->workflowItemRepository);

        $registry = $this->getMock(ManagerRegistry::class);
        $registry->expects($this->any())->method('getManagerForClass')->with(WorkflowItem::class)->willReturn($em);

        $this->cronVerifier = $this->getMock(ExpressionVerifierInterface::class);
        $this->filterVerifier = $this->getMock(ExpressionVerifierInterface::class);

        $this->verifier = new TransitionTriggerCronVerifier($this->workflowAssembler, $registry);
    }

    public function testAddOptionVerifier()
    {
        $this->assertAttributeEmpty('optionVerifiers', $this->verifier);

        $this->verifier->addOptionVerifier('test', $this->cronVerifier);

        $this->assertAttributeCount(1, 'optionVerifiers', $this->verifier);
        $this->assertAttributeEquals(['test' => [$this->cronVerifier]], 'optionVerifiers', $this->verifier);

        $this->verifier->addOptionVerifier('test', $this->filterVerifier);

        $this->assertAttributeCount(1, 'optionVerifiers', $this->verifier);
        $this->assertAttributeEquals(
            ['test' => [$this->cronVerifier, $this->filterVerifier]],
            'optionVerifiers',
            $this->verifier
        );
    }

    public function testVerify()
    {
        $cron = '* * * * *';
        $filter = 'e.test = data';
        $query = $this->getMockBuilder(AbstractQuery::class)->disableOriginalConstructor()->getMock();
        $transitionName = 'test_transition';
        $expectedStep = $this->getStep('step2', [$transitionName]);
        $workflow = $this->getWorkflow([$this->getStep('step1', ['invalid_transition']), $expectedStep]);

        $this->cronVerifier->expects($this->once())->method('verify')->with($cron);
        $this->filterVerifier->expects($this->once())->method('verify')->with($query);

        $testVerifier = $this->getMock(ExpressionVerifierInterface::class);
        $testVerifier->expects($this->never())->method($this->anything());

        $this->verifier->addOptionVerifier('cron', $this->cronVerifier);
        $this->verifier->addOptionVerifier('filter', $this->filterVerifier);
        $this->verifier->addOptionVerifier('test', $testVerifier);

        $this->workflowAssembler->expects($this->once())
            ->method('assemble')
            ->with($workflow->getDefinition(), false)
            ->willReturn($workflow);

        $this->workflowItemRepository->expects($this->once())
            ->method('getIdsByStepNamesAndEntityClassQueryBuilder')
            ->with(
                new ArrayCollection([$expectedStep->getName() => $expectedStep->getName()]),
                self::ENTITY_CLASS,
                $filter
            )
            ->willReturn($this->setUpQueryBuilder($query));

        $this->verifier->verify($this->getTrigger($workflow, $transitionName, $cron, $filter));
    }

    /**
     * @param Workflow $workflow
     * @param string $transitionName
     * @param string $cron
     * @param string $filter
     * @return TransitionCronTrigger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTrigger(Workflow $workflow, $transitionName, $cron, $filter)
    {
        $trigger = $this->getMockBuilder(TransitionCronTrigger::class)->disableOriginalConstructor()->getMock();
        $trigger->expects($this->any())->method('getCron')->willReturn($cron);
        $trigger->expects($this->any())->method('getFilter')->willReturn($filter);
        $trigger->expects($this->any())->method('getWorkflowDefinition')->willReturn($workflow->getDefinition());
        $trigger->expects($this->any())->method('getTransitionName')->willReturn($transitionName);
        $trigger->expects($this->any())->method('getEntityClass')->willReturn(self::ENTITY_CLASS);

        return $trigger;
    }

    /**
     * @param array $steps
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkflow(array $steps)
    {
        $workflowDefinition = $this->getEntity(WorkflowDefinition::class, ['relatedEntity' => self::ENTITY_CLASS]);
        $stepManager = new StepManager($steps);

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->any())->method('getDefinition')->willReturn($workflowDefinition);
        $workflow->expects($this->any())->method('getStepManager')->willReturn($stepManager);

        return $workflow;
    }

    /**
     * @param string $name
     * @param array $allowedTransitions
     * @return Step
     */
    protected function getStep($name, array $allowedTransitions)
    {
        $step = new Step();
        $step->setName($name)->setAllowedTransitions($allowedTransitions);

        return $step;
    }

    /**
     * @param AbstractQuery $query
     * @return QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function setUpQueryBuilder(AbstractQuery $query)
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        return $qb;
    }
}
