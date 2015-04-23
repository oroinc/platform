<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;
use Oro\Bundle\SegmentBundle\Tests\Unit\Fixtures\StubEntity;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;

class SegmentSnapshotRepositoryTest extends SegmentDefinitionTestCase
{
    const ENTITY_NAME = 'OroSegmentBundle:SegmentSnapshot';

    /** @var SegmentSnapshotRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $em;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getClassMetadata', 'createQueryBuilder', 'beginTransaction', 'commit'])
            ->getMock();

        $this->repository = new SegmentSnapshotRepository(
            $this->em,
            new ClassMetadata('OroSegmentBundle:SegmentSnapshot')
        );
    }

    protected function tearDown()
    {
        unset($this->em, $this->repository);
    }

    public function testRemoveBySegment()
    {
        $segment = $this->getSegment();

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()->setMethods(['execute'])
            ->getMockForAbstractClass();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['delete', 'where', 'setParameter', 'getQuery', 'execute'])
            ->getMock();
        $queryBuilder->expects($this->once())->method('delete')->with(self::ENTITY_NAME, 'snp')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('where')->with('snp.segment = :segment')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('setParameter')->with('segment', $segment)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('getQuery')
            ->will($this->returnValue($query));

        $this->em->expects($this->once())->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $this->repository->removeBySegment($segment);
    }

    /**
     * @param string $type
     * @param string $expected
     *
     * @dataProvider identifierDataProvider
     */
    public function testGetIdentifiersSelectQueryBuilder($type, $expected)
    {
        $segment = $this->getSegment();

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('field'));

        $metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with($this->equalTo('field'))
            ->will($this->returnValue($type));

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['select', 'from', 'where', 'setParameter'])
            ->getMock();
        $queryBuilder->expects($this->at(0))->method('select')->with('snp')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('from')->with(self::ENTITY_NAME, 'snp')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(2))->method('select')->with($expected)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('where')->with('snp.segment = :segment')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('setParameter')->with('segment', $segment)
            ->will($this->returnSelf());

        $this->em->expects($this->once())->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $result = $this->repository->getIdentifiersSelectQueryBuilder($segment);
        $this->assertSame($queryBuilder, $result);
    }

    /**
     * @return array
     */
    public function identifierDataProvider()
    {
        return [
            ['integer', 'snp.integerEntityId'],
            ['string', 'snp.entityId'],
        ];
    }

    public function testRemoveByEntity()
    {
        $entity = $this->createEntities();

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()->setMethods(['execute'])
            ->getMockForAbstractClass();

        $queryBuilder = $this->mockGetSnapshotDeleteQueryBuilderByEntitiesFunction($entity);
        $queryBuilder->expects($this->exactly(2))
            ->method('getQuery')
            ->will($this->returnValue($query));
        $this->repository->removeByEntity(reset($entity));
    }

    protected function createEntities($count = 1)
    {
        $entities = [];
        for ($i = 1; $i <= $count; $i++) {
            $entity = new StubEntity();
            $entity->setId($i);
            $entity->setName('name-' . $i);
            $entities[] = $entity;
        }
        return $entities;
    }

    protected function mockGetSnapshotDeleteQueryBuilderByEntitiesFunction(array $entities, $callCount = 1)
    {
        $result = [];
        /** @var StubEntity $entity */
        foreach ($entities as $entity) {
            $result[] = [
                'entity' => get_class($entity),
                'id'     => $entity->getId()
            ];
        }
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()->setMethods(['getResult', 'execute'])
            ->getMockForAbstractClass();
        $query->expects($this->exactly($callCount))
            ->method('getResult')
            ->will($this->returnValue($result));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['delete', 'select', 'from', 'orWhere', 'setParameter', 'getQuery', 'expr'])
            ->getMock();

        $expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->setMethods(['in', 'andX'])
            ->getMock();
        $expr->expects($this->exactly($callCount))
            ->method('andX')
            ->will($this->returnSelf());
        $expr->expects($this->exactly($callCount * 2))
            ->method('in')
            ->will($this->returnSelf());

        $queryBuilder->expects($this->exactly($callCount * 3))
            ->method('expr')
            ->will($this->returnValue($expr));
        $this->em->expects($this->exactly($callCount * 2))
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $queryBuilder->expects($this->exactly($callCount))
            ->method('delete')
            ->with(self::ENTITY_NAME, 'snp')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->exactly($callCount))
            ->method('select')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->exactly($callCount))
            ->method('from')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $callCount *= count($entities);

        $queryBuilder->expects($this->exactly($callCount * 2))
            ->method('orWhere')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->exactly($callCount))
            ->method('setParameter')
            ->will($this->returnSelf());

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->setMethods(['getIdentifierValues'])
            ->getMock();
        $metadata->expects($this->exactly($callCount))
            ->method('getIdentifierValues')
            ->will($this->returnCallback(
                function (StubEntity $currentEntity) {
                    return [$currentEntity->getId()];
                }
            ));

        $this->em->expects($this->exactly($callCount))
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        return $queryBuilder;
    }
}
