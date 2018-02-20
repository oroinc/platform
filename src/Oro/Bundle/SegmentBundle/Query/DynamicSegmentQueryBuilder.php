<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\RestrictionSegmentProxy;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class DynamicSegmentQueryBuilder implements QueryBuilderInterface
{
    /** @var ServiceLink */
    protected $segmentQueryConverterFactoryLink;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ServiceLink     $segmentQueryConverterFactoryLink
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        ServiceLink $segmentQueryConverterFactoryLink,
        ManagerRegistry $doctrine
    ) {
        $this->segmentQueryConverterFactoryLink = $segmentQueryConverterFactoryLink;
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
        $em = $this->doctrine->getManagerForClass($segment->getEntity());
        $converter = $this->getConverter();
        $qb = $converter->convert(new RestrictionSegmentProxy($segment, $em));

        return $qb;
    }

    /**
     * @return SegmentQueryConverter
     */
    protected function getConverter()
    {
        /** @var SegmentQueryConverterFactory $factory */
        $factory = $this->segmentQueryConverterFactoryLink->getService();

        return  $factory->createInstance();
    }
}
