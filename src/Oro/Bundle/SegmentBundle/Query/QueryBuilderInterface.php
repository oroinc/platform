<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Oro\Bundle\SegmentBundle\Entity\Segment;

interface QueryBuilderInterface
{
    /**
     * Builds query based on segment definition
     * Returns query that could be applied in WHERE statement for filtering by segment conditions
     *
     * @param Segment $segment
     *
     * @return Query
     *
     * @throws \LogicException
     */
    public function build(Segment $segment);

    /**
     * Builds QueryBuilder based on segment definition
     * Returns QueryBuilder that could be used for filtering by segment conditions
     *
     * @param Segment $segment
     *
     * @return QueryBuilder
     *
     * @throws \LogicException
     */
    public function getQueryBuilder(Segment $segment);
}
