<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WorkflowBundle\Autocomplete\WorkflowReplacementSearchHandler;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowReplacementSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'stdClass';

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryBuilder;

    /** @var Query|\PHPUnit_Framework_MockObject_MockObject */
    protected $query;

    /** @var WorkflowRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowRegistry;

    /** @var WorkflowReplacementSearchHandler */
    protected $searchHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowRegistry = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository->expects($this->any())->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())->method('getQuery')->willReturn($this->query);
        $this->queryBuilder->expects($this->any())->method('expr')->willReturn(new Query\Expr());

        $this->searchHandler = new WorkflowReplacementSearchHandler(self::TEST_ENTITY_CLASS, ['label']);
        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($this->getManagerRegistryMock());
        $this->searchHandler->setWorkflowRegistry($this->workflowRegistry);
    }

    public function testSearchWithoutDelimiter()
    {
        $this->assertSame(
            [
                'results' => [],
                'more' => false
            ],
            $this->searchHandler->search('test', 1, 10)
        );
    }

    public function testSearchWithoutEntityAndSearchTerm()
    {
        $this->queryBuilder->expects($this->once())->method('setFirstResult')->with(0)
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())->method('setMaxResults')->with(11)
            ->willReturn($this->queryBuilder);

        $this->query->expects($this->once())->method('getResult')->willReturn([
            $this->getDefinition('item1', 'label1', true),
        ]);

        $this->assertSame(
            [
                'results' => [
                    ['name' => 'item1', 'label' => 'label1'],
                ],
                'more' => false
            ],
            $this->searchHandler->search(';', 1, 10)
        );
    }

    public function testSearchWithSearchTerm()
    {
        $this->queryBuilder->expects($this->once())->method('setFirstResult')->with(10)
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())->method('setMaxResults')->with(11)
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('andWhere')
            ->with((new Query\Expr())->like('w.label', ':search'))
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('setParameter')
            ->with('search', '%item2%')
            ->willReturn($this->queryBuilder);

        $this->query->expects($this->once())->method('getResult')->willReturn([
            $this->getDefinition('item2', 'label2', true),
        ]);

        $this->assertSame(
            [
                'results' => [
                    ['name' => 'item2', 'label' => 'label2'],
                ],
                'more' => false
            ],
            $this->searchHandler->search('item2;', 2, 10)
        );
    }

    public function testSearchWithEntity()
    {
        $this->queryBuilder->expects($this->once())->method('setFirstResult')->with(10)
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())->method('setMaxResults')->with(11)
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('andWhere')
            ->with((new Query\Expr())->notIn('w.name', ':id'))
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('setParameter')
            ->with('id', ['entity1'])
            ->willReturn($this->queryBuilder);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('entity1')
            ->willReturn(null);

        $this->query->expects($this->once())->method('getResult')->willReturn([
            $this->getDefinition('item3', 'label3', true),
        ]);

        $this->assertSame(
            [
                'results' => [
                    ['name' => 'item3', 'label' => 'label3'],
                ],
                'more' => false
            ],
            $this->searchHandler->search(';entity1', 2, 10)
        );
    }

    public function testSearchWithEntityAndWorkflowsWithSameGroup()
    {
        $this->queryBuilder->expects($this->once())->method('setFirstResult')->with(10)
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())->method('setMaxResults')->with(11)
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('andWhere')
            ->with((new Query\Expr())->notIn('w.name', ':id'))
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('setParameter')
            ->with('id', ['entity1', 'entity2', 'entity3'])
            ->willReturn($this->queryBuilder);

        $workflow = $this->getWorkflowMock('entity1');

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('entity1')
            ->willReturn($workflow);

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->with(['entity1_group'])
            ->willReturn([$workflow, $this->getWorkflowMock('entity2'), $this->getWorkflowMock('entity3')]);

        $this->query->expects($this->once())->method('getResult')->willReturn([
            $this->getDefinition('item3', 'label3', true),
        ]);

        $this->assertSame(
            [
                'results' => [
                    ['name' => 'item3', 'label' => 'label3'],
                ],
                'more' => false
            ],
            $this->searchHandler->search(';entity1', 2, 10)
        );
    }

    public function testSearchFilterByIsActiveWorkflow()
    {
        $this->queryBuilder->expects($this->once())->method('setFirstResult')->with(0)
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())->method('setMaxResults')->with(11)
            ->willReturn($this->queryBuilder);

        $this->query->expects($this->once())->method('getResult')->willReturn([
            $this->getDefinition('item4', 'label4', false),
            $this->getDefinition('item5', 'label5', true),
        ]);

        $this->assertSame(
            [
                'results' => [
                    ['name' => 'item5', 'label' => 'label5'],
                ],
                'more' => false
            ],
            $this->searchHandler->search(';', 1, 10)
        );
    }

    /**
     * @param string $name
     * @param string $label
     * @param bool $active
     * @return WorkflowDefinition
     */
    protected function getDefinition($name, $label, $active)
    {
        $definition = new WorkflowDefinition();
        $definition
            ->setName($name)
            ->setLabel($label)
            ->setActive($active);

        return $definition;
    }

    /**
     * @return ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManagerRegistryMock()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($this->getMetadataFactoryMock());
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($this->entityRepository);

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($entityManager);

        return $managerRegistry;
    }

    /**
     * @return ClassMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMetadataFactoryMock()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('name');

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($metadata);

        return $metadataFactory;
    }

    /**
     * @param string $name
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkflowMock($name)
    {
        $definition = $this->getMockBuilder(WorkflowDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->expects($this->any())->method('getName')->willReturn($name);
        $definition->expects($this->any())->method('getActiveGroups')->willReturn([$name . '_group']);

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->any())->method('getName')->willReturn($name);
        $workflow->expects($this->any())->method('getDefinition')->willReturn($definition);

        return $workflow;
    }
}
