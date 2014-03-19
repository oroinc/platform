<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Oro\Bundle\SegmentBundle\Entity\Segment;

interface QueryBuilderInterface
{
    /**
     * Builds query based on segment definition
     * Returns query that could be applied in WHERE statement for filtering by segment conditions
     *
     * @param Segment $segment
     *
     * @return \Doctrine\ORM\Query
     * @throws \LogicException
     */
    public function build(Segment $segment);
}
