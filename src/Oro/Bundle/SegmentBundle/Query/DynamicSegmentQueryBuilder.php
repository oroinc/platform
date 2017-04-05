<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\EntityManager;

use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\RestrictionSegmentProxy;

class DynamicSegmentQueryBuilder implements QueryBuilderInterface
{
    /** @var ServiceLink */
    protected $segmentQueryConverterLink;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ServiceLink     $segmentQueryConverterLink
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ServiceLink $segmentQueryConverterLink, ManagerRegistry $doctrine)
    {
        $this->segmentQueryConverterLink = $segmentQueryConverterLink;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function build(Segment $segment)
    {
        $qb = $this->getQueryBuilder($segment);

        return $qb->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder(Segment $segment)
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($segment->getEntity());

        /** @var SegmentQueryConverter $segmentQueryConverter */
        $segmentQueryConverter = $this->segmentQueryConverterLink->getService();
        $qb = $segmentQueryConverter->convert(new RestrictionSegmentProxy($segment, $em));

        return $qb;
    }
}
