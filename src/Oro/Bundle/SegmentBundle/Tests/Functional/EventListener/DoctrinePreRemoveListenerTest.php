<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DoctrinePreRemoveListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            LoadSegmentSnapshotData::class,
        ]);
    }

    public function testSegmentSnapshotActualizationOnEntityRemoveWhenExecutedWithinJob()
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);

        $registry = $this->getContainer()->get('doctrine');

        $job = $this->createJob();
        $jobEm = $registry->getManagerForClass(Job::class);
        $jobEm->persist($job);
        $jobEm->flush($job);

        $entity = $this->getReference('workflow_aware_entity_1');
        $entityId = $entity->getId();

        $segmentSnapshotRepository = $registry->getManagerForClass(SegmentSnapshot::class)
            ->getRepository(SegmentSnapshot::class);

        $snapshotIds = array_column(
            $segmentSnapshotRepository->getIdentifiersSelectQueryBuilder($segment)
                ->getQuery()
                ->getArrayResult(),
            SegmentSnapshot::ENTITY_REF_INTEGER_FIELD
        );
        $this->assertContains($entityId, $snapshotIds);

        $em = $registry->getManagerForClass(ClassUtils::getClass($entity));
        $em->remove($entity);

        $jobEm->remove($job);
        $jobEm->flush();

        $em->flush();

        $snapshotIds = array_column(
            $segmentSnapshotRepository->getIdentifiersSelectQueryBuilder($segment)
                ->getQuery()
                ->getArrayResult(),
            SegmentSnapshot::ENTITY_REF_INTEGER_FIELD
        );
        $this->assertNotContains($entityId, $snapshotIds);
    }

    /**
     * @return Job
     */
    protected function createJob()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $job = new Job();
        $job->setName('test');
        $job->setStatus(Job::STATUS_CANCELLED);
        $job->setCreatedAt($date);
        $job->setStartedAt($date);
        $job->setLastActiveAt($date);

        return $job;
    }
}
