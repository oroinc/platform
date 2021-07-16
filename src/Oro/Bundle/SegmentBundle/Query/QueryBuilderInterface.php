<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Represents a segment query builder.
 */
interface QueryBuilderInterface
{
    /**
     * Builds an ORM query based on the given segment definition.
     * The returned query could be applied in WHERE statement for filtering by segment conditions.
     */
    public function build(Segment $segment): Query;

    /**
     * Builds an ORM query builder based on the given segment definition.
     * The returned query builder could be used for filtering by segment conditions.
     */
    public function getQueryBuilder(Segment $segment): QueryBuilder;
}
