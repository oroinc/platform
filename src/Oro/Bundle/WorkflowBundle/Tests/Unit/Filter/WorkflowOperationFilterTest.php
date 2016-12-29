<?php

namespace Oro\Bundle\WorkflowBundle\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowOperationFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowDefinitionRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var WorkflowOperationFilter */
    private $filter;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(WorkflowDefinitionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(WorkflowDefinition::class)
            ->willReturn($manager);

        $this->filter = new WorkflowOperationFilter($registry);
    }

    /**
     * @dataProvider filterDataProvider
     *
     * @param array $disabledOperationsConfigs
     * @param array $operationsToFilter
     * @param OperationFindCriteria $findCriteria
     * @param array $expected
     */
    public function testFilter(
        array $disabledOperationsConfigs,
        array $operationsToFilter,
        OperationFindCriteria $findCriteria,
        array $expected
    ) {
        $this->setUpWorkflowDefinitionRepository($disabledOperationsConfigs);

        $this->assertEquals($expected, $this->filter->filter($operationsToFilter, $findCriteria));
    }

    public function testWontLoadConfigsTwice()
    {
        $this->setUpWorkflowDefinitionRepository([['operation1' => []]]);

        //run first with initialization
        $result = $this->filter->filter(
            ['operation1' => $this->createOperation('operation1')],
            new OperationFindCriteria(null, null, null)
        );

        $this->assertEmpty($result);

        //runs second without initialization
        $result = $this->filter->filter(
            ['operation2' => $this->createOperation('operation2')],
            new OperationFindCriteria(null, null, null)
        );

        $this->assertEquals(['operation2' => $this->createOperation('operation2')], $result);
    }

    /**
     * @return \Generator
     */
    public function filterDataProvider()
    {
        $operation1 = $this->createOperation('operation1');
        $operation2 = $this->createOperation('operation2');
        $operation3 = $this->createOperation('operation3');

        yield 'wont filter if no configs met' => [
            'disabledOperationsConfigs' => [
                [],
                [],
                []
            ],
            'operationsToFilter' => ['first' => $operation1, 'second' => $operation2],
            'criteria' => new OperationFindCriteria('entityClass1', null, null),
            'expected' => ['first' => $operation1, 'second' => $operation2]
        ];

        yield 'not filtered' => [
            'disabledOperationsConfigs' => [
                [],
                ['operation3' => []],
                ['operation3' => ['entityClass1']]
            ],
            'operationsToFilter' => ['first' => $operation1, 'second' => $operation2],
            'criteria' => new OperationFindCriteria('entityClass1', null, null),
            'expected' => ['first' => $operation1, 'second' => $operation2]
        ];

        yield 'filtered by name only' => [
            'disabledOperationsConfigs' => [
                ['operation1' => []]
            ],
            'operationsToFilter' => ['first' => $operation1, 'second' => $operation2],
            'criteria' => new OperationFindCriteria('entityClass1', null, null),
            'expected' => ['second' => $operation2]
        ];

        yield 'filtered by one wildcard' => [
            'disabledOperationsConfigs' => [
                ['operation3' => []], //wildcard
                ['operation2' => ['entityClass1']]
            ],
            'operationsToFilter' => ['first' => $operation1, 'second' => $operation2, 'third' => $operation3],
            'criteria' => new OperationFindCriteria('entityClass2', null, null),
            'expected' => ['first' => $operation1, 'second' => $operation2]
        ];

        yield 'filtered by wildcard and class and keep with non matched class' => [
            'disabledOperationsConfigs' => [
                ['operation3' => []], //wildcard
                ['operation2' => ['entityClass2']],
                ['operation1' => ['entityClass1']]
            ],
            'operationsToFilter' => ['first' => $operation1, 'second' => $operation2, 'third' => $operation3],
            'criteria' => new OperationFindCriteria('entityClass2', null, null),
            'expected' => ['first' => $operation1]
        ];

        yield 'filtered by merged class names' => [
            'disabledOperationsConfigs' => [
                ['operation2' => ['entityClass1']], //wildcard
                ['operation2' => ['entityClass2']]
            ],
            'operationsToFilter' => ['first' => $operation1, 'second' => $operation2, 'third' => $operation3],
            'criteria' => new OperationFindCriteria('entityClass2', null, null),
            'expected' => ['first' => $operation1, 'third' => $operation3]
        ];
    }

    /**
     * @param string $name
     * @return \PHPUnit_Framework_MockObject_MockObject|Operation
     */
    private function createOperation($name)
    {
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $operation->expects($this->any())->method('getName')->willReturn($name);
        $operation->expects($this->any())->method('getDefinition')->willReturn(new OperationDefinition());

        return $operation;
    }

    /**
     * @param array $disabledOperationsConfigs
     */
    private function setUpWorkflowDefinitionRepository(array $disabledOperationsConfigs)
    {
        $this->repository->expects($this->once())
            ->method('findActive')
            ->willReturn($this->createWorkflowDefinitionsWithConfig($disabledOperationsConfigs));
    }

    /**
     * @param array $disabledOperationsConfigs
     * @return array|WorkflowDefinition[]
     */
    private function createWorkflowDefinitionsWithConfig(array $disabledOperationsConfigs)
    {
        $definitions = [];

        foreach ($disabledOperationsConfigs as $config) {
            $definitions[] = (new WorkflowDefinition())->setConfiguration(
                [WorkflowConfiguration::NODE_DISABLE_OPERATIONS => $config]
            );
        }

        return $definitions;
    }
}
