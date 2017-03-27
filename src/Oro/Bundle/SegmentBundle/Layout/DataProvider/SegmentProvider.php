<?php

namespace Oro\Bundle\SegmentBundle\Layout\DataProvider;

use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentProvider
{
    /** @var SegmentManager */
    private $manager;

    /**
     * @param SegmentManager $manager
     */
    public function __construct(SegmentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param int $segmentId
     *
     * @return array
     */
    public function getCollection($segmentId)
    {
        /** @var Segment $segment */
        $segment = $this->manager->findById($segmentId);
        if ($segment !== null) {
            $qb = $this->manager->getEntityQueryBuilder($segment);
            if ($qb !== null) {
                return $qb->getQuery()->getResult();
            }
        }

        return [];
    }
}
