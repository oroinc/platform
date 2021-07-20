<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;

/**
 * The query builder for static segments.
 */
class StaticSegmentQueryBuilder implements QueryBuilderInterface
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function build(Segment $segment): Query
    {
        return $this->getQueryBuilder($segment)->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder(Segment $segment): QueryBuilder
    {
        return $this->em->getRepository(SegmentSnapshot::class)
            ->getIdentifiersSelectQueryBuilder($segment);
    }
}
