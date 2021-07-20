<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\DynamicSegmentQueryDesigner;

/**
 * The query builder for dynamic segments.
 */
class DynamicSegmentQueryBuilder implements QueryBuilderInterface
{
    /** @var SegmentQueryConverterFactory */
    private $segmentQueryConverterFactory;

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(
        SegmentQueryConverterFactory $segmentQueryConverterFactory,
        ManagerRegistry $doctrine
    ) {
        $this->segmentQueryConverterFactory = $segmentQueryConverterFactory;
        $this->doctrine = $doctrine;
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
        return $this->segmentQueryConverterFactory->createInstance()
            ->convert(new DynamicSegmentQueryDesigner(
                $segment,
                $this->doctrine->getManagerForClass($segment->getEntity())
            ));
    }
}
