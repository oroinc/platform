<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Model\TransitionScheduleHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionScheduleHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var TransitionScheduleHelper */
    protected $helper;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()->getMock();
        $this->helper = new TransitionScheduleHelper($this->registry, $this->workflowManager);
    }

    /**
     * @dataProvider createQueryDataProvider
     * @param array $steps
     * @param string $dqlFilter
     */
    public function testCreateQuery(array $steps, $dqlFilter)
    {
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('EntityClass')
            ->willReturn($em);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->with('EntityClass')
            ->willReturn($repository);

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('select')->with('wi.id')->willReturn($queryBuilder);

        $queryBuilder->expects($this->at(1))->method('innerJoin')
            ->with('e.workflowItem', 'wi') ->willReturn($queryBuilder);
        $queryBuilder->expects($this->at(2))->method('innerJoin')
            ->with('e.workflowStep', 'ws')->willReturn($queryBuilder);
        $queryBuilder->expects($this->at(3))->method('innerJoin')
            ->with('wi.definition', 'wd')->willReturn($queryBuilder);

        $whereClauseMock = $this->getMockBuilder('\Doctrine\ORM\Query\Expr\Func')
            ->disableOriginalConstructor()
            ->getMock();

        $expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')->getMock();

        $queryBuilder->expects($this->once())->method('expr')->willReturn($expr);
        $expr->expects($this->once())->method('in')->with('ws.name', ':workflowSteps')->willReturn($whereClauseMock);

        $queryBuilder->expects($this->once())->method('where')->with($whereClauseMock)->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())->method('setParameter')->with('workflowSteps', $steps);

        if ($dqlFilter) {
            $queryBuilder->expects($this->once())->method('andWhere')->with($dqlFilter);
        }

        $this->helper->createQuery($steps, 'EntityClass', $dqlFilter);
    }

    public function createQueryDataProvider()
    {
        return [
            'without dql' => [
                ['step1', 'step2'],
                null
            ],
            'with dql' => [
                ['step1', 'step2'],
                'custom filter dql expression'
            ]
        ];
    }
}
