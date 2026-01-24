<?php

namespace Oro\Bundle\SegmentBundle\Layout\DataProvider;

use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Data provider for retrieving segment collections in layout templates.
 *
 * This provider retrieves the entities that match a segment's criteria and makes them
 * available for rendering in layout templates. It uses the segment manager to load the
 * segment definition and build a query that returns all entities matching the segment's
 * conditions. The provider returns an empty array if the segment is not found or if no
 * query builder can be generated for the segment.
 */
class SegmentProvider
{
    /** @var SegmentManager */
    private $manager;

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
