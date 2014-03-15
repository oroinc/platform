<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

class StaticSegmentManager
{
    /** @var EntityManager */
    protected $em;

    /** @var DynamicSegmentQueryBuilder */
    protected $dynamicSegmentQB;

    /** @var array */
    private $toWrite;

    /** @var int */
    private $batchSize = 100;

    /**
     * @param EntityManager              $em
     * @param DynamicSegmentQueryBuilder $dynamicSegmentQB
     */
    public function __construct(EntityManager $em, DynamicSegmentQueryBuilder $dynamicSegmentQB)
    {
        $this->em               = $em;
        $this->dynamicSegmentQB = $dynamicSegmentQB;
    }

    /**
     * Runs static repository restriction query and stores it state into snapshot entity
     *
     * @param Segment $segment
     *
     * @throws \LogicException
     * @throws \Exception
     */
    public function run(Segment $segment)
    {
        if ($segment->getType()->getName() !== SegmentType::TYPE_STATIC) {
            throw new \LogicException('Only static segments could have snapshots.');
        }

        $this->em->getRepository('OroSegmentBundle:SegmentSnapshot')->removeBySegment($segment);

        $qb       = $this->dynamicSegmentQB->build($segment);
        $iterator = new BufferedQueryResultIterator($qb);

        $writeCount = 0;
        try {
            $this->em->beginTransaction();
            foreach ($iterator as $data) {
                // only not composite identifiers are supported
                $id = reset($data);

                $writeCount++;
                $snapshot = new SegmentSnapshot($segment);
                $snapshot->setEntityId($id);
                $this->toWrite[] = $snapshot;
                if (0 === $writeCount % $this->batchSize) {
                    $this->write($this->toWrite);

                    $this->toWrite = [];
                }
            }

            if (count($this->toWrite) > 0) {
                $this->write($this->toWrite);
            }

            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();

            throw $exception;
        }

        $segment = $this->em->merge($segment);
        $segment->setLastRun(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->em->persist($segment);
        $this->em->flush();
    }

    /**
     * Do persist into EntityManager
     *
     * @param array $items
     */
    private function write(array $items)
    {
        foreach ($items as $item) {
            $this->em->persist($item);
        }
        $this->em->flush();
        $this->em->clear();
    }
}
