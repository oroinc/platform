<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WorkflowBundle\Autocomplete\WorkflowReplacementSearchHandler;

class WorkflowReplacementSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'stdClass';

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryBuilder;

    /** @var Query|\PHPUnit_Framework_MockObject_MockObject */
    protected $query;

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

        $this->entityRepository->expects($this->any())->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())->method('getQuery')->willReturn($this->query);
        $this->queryBuilder->expects($this->any())->method('expr')->willReturn(new Query\Expr());

        $this->searchHandler = new WorkflowReplacementSearchHandler(self::TEST_ENTITY_CLASS, ['name']);
        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($this->getManagerRegistryMock());
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

    public function testSearchWithoutEntityAndWithoutSearchTerm()
    {
        $this->queryBuilder->expects($this->once())->method('setFirstResult')->with(0)
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())->method('setMaxResults')->with(11)
            ->willReturn($this->queryBuilder);

        $this->query->expects($this->once())->method('getResult')->willReturn([
            ['id' => 'item1', 'name' => 'name1'],
        ]);

        $this->assertSame(
            [
                'results' => [
                    ['id' => 'item1', 'name' => 'name1'],
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
            ['id' => 'item2', 'name' => 'name2'],
        ]);

        $this->assertSame(
            [
                'results' => [
                    ['id' => 'item2', 'name' => 'name2'],
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
            ->with((new Query\Expr())->notIn('w.id', ':id'))
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('setParameter')
            ->with('id', 'entity1')
            ->willReturn($this->queryBuilder);

        $this->query->expects($this->once())->method('getResult')->willReturn([
            ['id' => 'item3', 'name' => 'name3'],
        ]);

        $this->assertSame(
            [
                'results' => [
                    ['id' => 'item3', 'name' => 'name3'],
                ],
                'more' => false
            ],
            $this->searchHandler->search(';entity1', 2, 10)
        );
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
        $metadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($metadata);

        return $metadataFactory;
    }
}
