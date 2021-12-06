<?php
namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SegmentSnapshotRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadSegmentSnapshotData::class]);
    }

    private function getSegment(string $reference): Segment
    {
        return $this->getReference($reference);
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    private function getEntityRepository(string $entityClass): EntityRepository
    {
        return $this->getDoctrine()->getRepository($entityClass);
    }

    private function getSegmentSnapshotRepository(): SegmentSnapshotRepository
    {
        return $this->getDoctrine()->getRepository(SegmentSnapshot::class);
    }

    public function testRemoveBySegment()
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);

        $segmentSnapshotRepository = $this->getSegmentSnapshotRepository();

        self::assertNotEmpty($segmentSnapshotRepository->findBy(['segment' => $segment]));

        $segmentSnapshotRepository->removeBySegment($segment);

        self::assertEmpty($segmentSnapshotRepository->findBy(['segment' => $segment]));
    }

    public function testRemoveBySegmentWitIds()
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);

        $segmentSnapshotRepository = $this->getSegmentSnapshotRepository();

        /** @var SegmentSnapshot[] $segmentEntities */
        $segmentEntities = $segmentSnapshotRepository->findBy(['segment' => $segment]);
        self::assertNotEmpty($segmentEntities);

        $firstSnapshot = reset($segmentEntities);
        $entityId = $firstSnapshot->getIntegerEntityId();
        $segmentSnapshotRepository->removeBySegment($segment, [$entityId]);

        $actualSegmentEntities = $segmentSnapshotRepository->findBy(['segment' => $segment]);

        self::assertNotEmpty($segmentSnapshotRepository->findBy(['segment' => $segment]));
        self::assertEquals(1, (count($segmentEntities) - count($actualSegmentEntities)));
        $actualEntityIds = array_map(
            function (SegmentSnapshot $snapshot) {
                return $snapshot->getIntegerEntityId();
            },
            $actualSegmentEntities
        );
        self::assertNotContains($entityId, $actualEntityIds);
    }

    public function testRemoveByEntity()
    {
        $entities = $this->getEntities(1, true);
        $entity = reset($entities);

        $expectedCondition = $this->getExpectedResult([$entity]);

        $segmentSnapshotRepository = $this->getSegmentSnapshotRepository();

        self::assertNotEmpty($this->findSegmentSnapshots($segmentSnapshotRepository, $expectedCondition));
        $segmentSnapshotRepository->removeByEntity($entity);
        self::assertEmpty($this->findSegmentSnapshots($segmentSnapshotRepository, $expectedCondition));
    }

    /**
     * @dataProvider massRemoveByEntitiesProvider
     */
    public function testMassRemoveByEntities(int $count, bool $withSegmentSnapshot)
    {
        $entities = $this->getEntities($count, $withSegmentSnapshot);

        $expectedCondition = $this->getExpectedResult($entities);

        $segmentSnapshotRepository = $this->getSegmentSnapshotRepository();

        self::assertNotEmpty($this->findSegmentSnapshots($segmentSnapshotRepository, $expectedCondition));
        $segmentSnapshotRepository->massRemoveByEntities($entities);
        self::assertEmpty($this->findSegmentSnapshots($segmentSnapshotRepository, $expectedCondition));
    }

    public function massRemoveByEntitiesProvider(): array
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

    private function getExpectedResult(array $entities): array
    {
        $doctrine = $this->getDoctrine();
        $segmentQB = $this->getEntityRepository(Segment::class)->createQueryBuilder('s');
        $segmentQB->select('s.id, s.entity');
        $expectedCondition = [];

        foreach ($entities as $key => $entity) {
            $className = ClassUtils::getClass($entity);
            $metadata  = $doctrine->getManager()->getClassMetadata($className);
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

    private function findSegmentSnapshots(
        SegmentSnapshotRepository $segmentSnapshotRepository,
        array $expectedCondition
    ): array {
        $selectQB = $segmentSnapshotRepository->createQueryBuilder('snp');

        foreach ($expectedCondition as $params) {
            $suffix = uniqid();
            $selectQB->select('snp.id')
                ->orWhere($selectQB->expr()->andX(
                    $selectQB->expr()->in('snp.segment', ':segmentIds' . $suffix),
                    $selectQB->expr()->orX(
                        $selectQB->expr()->in('snp.entityId', ':entityIds' . $suffix),
                        $selectQB->expr()->in('snp.integerEntityId', ':integerEntityIds' . $suffix)
                    )
                ))
                ->setParameter('segmentIds' . $suffix, $params['segmentIds'])
                ->setParameter('entityIds' . $suffix, $params['entityIds'])
                ->setParameter('integerEntityIds' . $suffix, $params['entityIds']);
        }

        return $selectQB->getQuery()->getResult();
    }

    private function getEntities(int $count, bool $withSegmentSnapshot): array
    {
        $queryBuilder = $this->getEntityRepository(WorkflowAwareEntity::class)->createQueryBuilder('entity');

        if ($withSegmentSnapshot) {
            $queryBuilder
                ->innerJoin(
                    SegmentSnapshot::class,
                    'snp',
                    Join::WITH,
                    'snp.entityId = CONCAT(entity.id, \'\') or snp.integerEntityId = entity.id'
                );
        } else {
            $queryBuilder
                ->leftJoin(
                    SegmentSnapshot::class,
                    'snp',
                    Join::WITH,
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->isNull('snp.entityId'),
                        $queryBuilder->expr()->isNull('snp.integerEntityId')
                    )
                );
        }

        return $queryBuilder->setMaxResults($count)->getQuery()->getResult();
    }

    public function testGetIdentifiersSelectQueryBuilder()
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);
        $repository = $this->getSegmentSnapshotRepository();

        self::assertStringContainsString(
            'integerEntityId FROM Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot snp',
            $repository->getIdentifiersSelectQueryBuilder($segment)->getDQL()
        );
    }
}
