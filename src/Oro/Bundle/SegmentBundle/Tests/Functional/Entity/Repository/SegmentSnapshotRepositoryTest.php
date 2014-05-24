<?php
namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;

/**
 * @dbIsolation
 * @dbReindex
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
        /** @var EntityRepository $entityRepository */
        $entityRepository = $registry->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity');

        $entity = $entityRepository->createQueryBuilder('entity')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
        $expectedCondition = $this->getExpectedResult($registry, array($entity));

        $segmentSnapshotRepository->removeByEntity($entity);

        $this->assertSegmentSnapshotHasBeenDeletedCorrectly($segmentSnapshotRepository, $expectedCondition);
    }

    /**
     * @dataProvider massRemoveByEntitiesProvider
     * @param $count
     */
    public function testMassRemoveByEntities($count)
    {
        /** @var Registry $registry */
        $registry = $this->getContainer()->get('doctrine');
        /** @var SegmentSnapshotRepository $segmentSnapshotRepository */
        $segmentSnapshotRepository = $registry->getRepository('OroSegmentBundle:SegmentSnapshot');
        /** @var EntityRepository $entityRepository */
        $entityRepository = $registry->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity');

        $entities = $entityRepository->createQueryBuilder('entity')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();

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
            'one entity' => array(
                'count' => 1
            ),
            'two entity' => array(
                'count' => 2
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
            $expectedCondition[$className] = array(
                'entityId'   => reset($entityIds),
                'segmentIds' => array()
            );

            $segmentQB
                ->orWhere('s.entity = :className' . $key)
                ->setParameter('className' . $key, $className);
        }

        $result = $segmentQB->getQuery()->getResult();

        foreach ($result as $row) {
            $expectedCondition[$row['entity']]['segmentIds'][] = $row['id'];
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
            $suffix = $params['entityId'];
            $selectQB->select('snp.id')
                ->orWhere(
                    'snp.segment IN (:segmentIds' . $suffix . ') AND
                     snp.entityId = :entityId' . $suffix
                )
                ->setParameter('segmentIds' . $suffix, implode(',', $params['segmentIds']))
                ->setParameter('entityId' . $suffix, $params['entityId']);
        }

        $entities = $selectQB->getQuery()->getResult();

        $this->assertEmpty($entities);
    }
}
