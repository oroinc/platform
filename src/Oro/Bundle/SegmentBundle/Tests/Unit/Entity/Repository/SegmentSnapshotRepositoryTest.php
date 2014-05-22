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
            ->disableOriginalConstructor()->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['delete', 'where', 'setParameter', 'getQuery'])
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

    public function testGetIdentifiersSelectQueryBuilder()
    {
        $segment = $this->getSegment();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['select', 'from', 'where', 'setParameter'])
            ->getMock();
        $queryBuilder->expects($this->at(0))->method('select')->with('snp')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('from')->with(self::ENTITY_NAME, 'snp')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(2))->method('select')->with('snp.entityId')
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

    public function testRemoveByEntity()
    {
        $entity = $this->createEntities();
        $queryBuilder = $this->mockGetSnapshotDeleteQueryBuilderByEntitiesFunction($entity);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getResult')
            ->will($this->returnSelf());
        $this->repository->removeByEntity(reset($entity));
    }

    /**
     * @dataProvider massRemoveByEntitiesProvider
     * @param $entities
     * @param $batchSize
     */
    /*public function testMassRemoveByEntities($entities, $batchSize)
    {
        if (empty($entities)) {
            $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->disableOriginalConstructor()
                ->getMock();
            $queryBuilder->expects($this->never())
                ->method('getQuery')
                ->will($this->returnSelf());
            $queryBuilder->expects($this->never())
                ->method('getResult')
                ->will($this->returnSelf());
        } else {
            $actualBatchSize = $batchSize ? $batchSize : SegmentSnapshotRepository::DELETE_BATCH_SIZE;
            $callCount = ceil(count($entities) / $actualBatchSize);
            $queryBuilder = $this->mockGetSnapshotDeleteQueryBuilderByEntitiesFunction($entities, $callCount);
            $queryBuilder->expects($this->exactly($callCount))
                ->method('getQuery')
                ->will($this->returnSelf());
            $queryBuilder->expects($this->exactly($callCount))
                ->method('getResult')
                ->will($this->returnSelf());
        }

        $this->em->expects($this->once())
            ->method('beginTransaction')
            ->will($this->returnSelf());
        $this->em->expects($this->once())
            ->method('commit')
            ->will($this->returnSelf());

        $this->repository->massRemoveByEntities($entities, $batchSize);
    }*/

    protected function createEntities($count = 1)
    {
        $entities = array();
        for ($i = 0; $i < $count; $i++) {
            $entity = new StubEntity();
            $entity->setId($i);
            $entity->setName('name-' . $i);
            $entities[] = $entity;
        }
        return $entities;
    }

    protected function mockGetSnapshotDeleteQueryBuilderByEntitiesFunction(array $entities, $callCount = 1)
    {

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('delete', 'select', 'from', 'where', 'orWhere', 'setParameter', 'getQuery', 'getResult'))
            ->getMock();
        $queryBuilder->expects($this->exactly($callCount))
            ->method('delete')
            ->with(self::ENTITY_NAME, 'snp')
            ->will($this->returnSelf());
        $callCount *= count($entities);
        $queryBuilder->expects($this->exactly($callCount))
            ->method('select')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->exactly($callCount))
            ->method('from')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->exactly($callCount))
            ->method('where')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->exactly($callCount))
            ->method('orWhere')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->exactly($callCount*2))
            ->method('setParameter')
            ->will($this->returnSelf());

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->setMethods(array('getIdentifier', 'getFieldValue'))
            ->getMock();
        $metadata->expects($this->exactly($callCount))
            ->method('getIdentifier')
            ->will($this->returnValue(array('id')));
        $metadata->expects($this->exactly($callCount))
            ->method('getFieldValue')
            ->will($this->returnCallback(
                function (StubEntity $currentEntity) {
                    return $currentEntity->getId();
                }
            ));

        $this->em->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        return $queryBuilder;
    }

    /**
     * @return array
     */
    public function massRemoveByEntitiesProvider()
    {
        return [
            'no entities' => array(
                'entities'  => array(),
                'batchSize' => null
            ),
            'one entity' => array(
                'entities'  => $this->createEntities(),
                'batchSize' => null
            ),
            'many entity with default batch size' => array(
                'entities'  => $this->createEntities(10),
                'batchSize' => null
            ),
            'many entity with custom batch size' => array(
                'entities'  => $this->createEntities(SegmentSnapshotRepository::DELETE_BATCH_SIZE),
                'batchSize' => (int)(SegmentSnapshotRepository::DELETE_BATCH_SIZE / 2)
            ),
        ];
    }
}
