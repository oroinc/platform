<?php
namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;

/**
 * @dbIsolation
 */
class SegmentSnapshotRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(array('Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData'));
    }

    public function testRemoveByEntity()
    {
        /** @var Registry $registry */
        $registry = $this->getContainer()->get('doctrine');
        /** @var SegmentSnapshotRepository $segmentSnapshotRepository */
        $segmentSnapshotRepository = $registry->getRepository('OroSegmentBundle:SegmentSnapshot');

        $entities = $this->getEntities($registry, 1, true);
        $entity = reset($entities);

        $expectedCondition = $this->getExpectedResult($registry, array($entity));

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
        return array(
            'one entity with related segment snapshot' => array(
                'count'               => 1,
                'withSegmentSnapshot' => true
            ),
            'one entity without related segment snapshot' => array(
                'count'               => 1,
                'withSegmentSnapshot' => false
            ),
            'two entity with related segment snapshot' => array(
                'count'               => 2,
                'withSegmentSnapshot' => true
            ),
            'two entity without related segment snapshot' => array(
                'count'               => 2,
                'withSegmentSnapshot' => false
            )
        );
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
        $expectedCondition = array();

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
                    'snp.entityId = CONCAT(entity.id, \'\')'
                );
        } else {
            $queryBuilder
                ->leftJoin('OroSegmentBundle:SegmentSnapshot', 'snp')
                ->where($queryBuilder->expr()->isNull('snp.entityId'));
        }

        return $queryBuilder->setMaxResults($count)->getQuery()->getResult();
    }
}
