<?php
namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;

/**
 * @dbIsolationPerTest
 */
class SegmentSnapshotRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadSegmentSnapshotData::class]);
    }

    public function testRemoveBySegment()
    {
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);

        $registry = $this->getContainer()->get('doctrine');
        $segmentSnapshotRepository = $registry->getRepository('OroSegmentBundle:SegmentSnapshot');

        $this->assertNotEmpty($segmentSnapshotRepository->findBy(['segment' => $segment]));

        $segmentSnapshotRepository->removeBySegment($segment);

        $this->assertEmpty($segmentSnapshotRepository->findBy(['segment' => $segment]));
    }

    public function testRemoveBySegmentWitIds()
    {
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);

        $registry = $this->getContainer()->get('doctrine');
        $segmentSnapshotRepository = $registry->getRepository('OroSegmentBundle:SegmentSnapshot');

        /** @var SegmentSnapshot[] $segmentEntities */
        $segmentEntities = $segmentSnapshotRepository->findBy(['segment' => $segment]);
        $this->assertNotEmpty($segmentEntities);

        $firstSnapshot = reset($segmentEntities);
        $entityId = $firstSnapshot->getIntegerEntityId();
        $segmentSnapshotRepository->removeBySegment($segment, [$entityId]);

        $actualSegmentEntities = $segmentSnapshotRepository->findBy(['segment' => $segment]);

        $this->assertNotEmpty($segmentSnapshotRepository->findBy(['segment' => $segment]));
        $this->assertEquals(1, (count($segmentEntities) - count($actualSegmentEntities)));
        $actualEntityIds = array_map(
            function (SegmentSnapshot $snapshot) {
                return $snapshot->getIntegerEntityId();
            },
            $actualSegmentEntities
        );
        $this->assertNotContains($entityId, $actualEntityIds);
    }

    public function testRemoveByEntity()
    {
        /** @var Registry $registry */
        $registry = $this->getContainer()->get('doctrine');
        /** @var SegmentSnapshotRepository $segmentSnapshotRepository */
        $segmentSnapshotRepository = $registry->getRepository('OroSegmentBundle:SegmentSnapshot');

        $entities = $this->getEntities($registry, 1, true);
        $entity = reset($entities);

        $expectedCondition = $this->getExpectedResult($registry, [$entity]);

        $segmentSnapshotRepository->removeByEntity($entity);

        $this->assertSegmentSnapshotHasBeenDeletedCorrectly($segmentSnapshotRepository, $expectedCondition);
    }

    /**
     * @dataProvider massRemoveByEntitiesProvider
     * @param integer $count
     * @param boolean $withSegmentSnapshot
     */
    public function testMassRemoveByEntities($count, $withSegmentSnapshot)
    {
        /** @var Registry $registry */
        $registry = $this->getContainer()->get('doctrine');
        /** @var SegmentSnapshotRepository $segmentSnapshotRepository */
        $segmentSnapshotRepository = $registry->getRepository('OroSegmentBundle:SegmentSnapshot');

        $entities = $this->getEntities($registry, $count, $withSegmentSnapshot);

        $expectedCondition = $this->getExpectedResult($registry, $entities);

        $segmentSnapshotRepository->massRemoveByEntities($entities);

        $this->assertSegmentSnapshotHasBeenDeletedCorrectly($segmentSnapshotRepository, $expectedCondition);
    }

    /**
     * @return array
     */
    public function massRemoveByEntitiesProvider()
    {
        return [
            'one entity with related segment snapshot' => [
                'count'               => 1,
                'withSegmentSnapshot' => true
            ],
            'one entity without related segment snapshot' => [
                'count'               => 1,
                'withSegmentSnapshot' => false
            ],
            'two entity with related segment snapshot' => [
                'count'               => 2,
                'withSegmentSnapshot' => true
            ],
            'two entity without related segment snapshot' => [
                'count'               => 2,
                'withSegmentSnapshot' => false
            ]
        ];
    }

    /**
     * @param Registry $registry
     * @param array $entities
     * @return array
     */
    protected function getExpectedResult($registry, $entities)
    {
        /** @var SegmentSnapshotRepository $segmentRepository */
        $segmentRepository = $registry->getRepository('OroSegmentBundle:Segment');

        $segmentQB = $segmentRepository->createQueryBuilder('s');
        $segmentQB->select('s.id, s.entity');
        $expectedCondition = [];

        foreach ($entities as $key => $entity) {
            $className = ClassUtils::getClass($entity);
            $metadata  = $registry->getManager()->getClassMetadata($className);
            $entityIds = $metadata->getIdentifierValues($entity);

            if (!isset($deleteParams[$className])) {
                $segmentQB
                    ->orWhere('s.entity = :className' . $key)
                    ->setParameter('className' . $key, $className);
            }

            $expectedCondition[$className]['entityIds'][] = (string)reset($entityIds);
        }

        $segments = $segmentQB->getQuery()->getResult();

        foreach ($segments as $segment) {
            $expectedCondition[$segment['entity']]['segmentIds'][] = $segment['id'];
        }

        return $expectedCondition;
    }

    /**
     * @param SegmentSnapshotRepository $segmentSnapshotRepository
     * @param array $expectedCondition
     */
    protected function assertSegmentSnapshotHasBeenDeletedCorrectly($segmentSnapshotRepository, $expectedCondition)
    {
        $selectQB = $segmentSnapshotRepository->createQueryBuilder('snp');

        foreach ($expectedCondition as $params) {
            $suffix = uniqid();
            $selectQB->select('snp.id')
                ->orWhere($selectQB->expr()->andX(
                    $selectQB->expr()->in('snp.segment', ':segmentIds' . $suffix),
                    $selectQB->expr()->in('snp.entityId', ':entityIds' . $suffix)
                ))
                ->setParameter('segmentIds' . $suffix, $params['segmentIds'])
                ->setParameter('entityIds' . $suffix, $params['entityIds']);
        }

        $entities = $selectQB->getQuery()->getResult();

        $this->assertEmpty($entities);
    }

    /**
     * @param Registry $registry
     * @param int $count
     * @param boolean $withSegmentSnapshot
     * @return array
     */
    protected function getEntities($registry, $count, $withSegmentSnapshot)
    {
        /** @var EntityRepository $entityRepository */
        $entityRepository = $registry->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity');
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $entityRepository->createQueryBuilder('entity');

        if ($withSegmentSnapshot) {
            $queryBuilder
                ->innerJoin(
                    'OroSegmentBundle:SegmentSnapshot',
                    'snp',
                    Join::WITH,
                    'snp.integerEntityId = entity.id'
                );
        } else {
            $queryBuilder
                ->leftJoin('OroSegmentBundle:SegmentSnapshot', 'snp')
                ->where($queryBuilder->expr()->isNull('snp.integerEntityId'));
        }

        return $queryBuilder->setMaxResults($count)->getQuery()->getResult();
    }
}
