<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Component\DependencyInjection\ServiceLink;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\RestrictionSegmentProxy;

class DynamicSegmentQueryBuilder implements QueryBuilderInterface
{
    /** @var ServiceLink */
    protected $segmentQueryConverterLink;

    /** @var ServiceLink */
    protected $segmentQueryConverterFactoryLink;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var SegmentQueryConverterFactory */
    protected $segmentQueryConverterFactory;

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
     * @deprecated this method will be removed in 2.4.
     * segmentQueryConverterFactoryLink will be injected in constructor
     * instead of segmentQueryConverterLink
     *
     * @param ServiceLink $segmentQueryConverterFactoryLink
     */
    public function setSegmentQueryConverterFactoryLink(ServiceLink $segmentQueryConverterFactoryLink)
    {
        $this->segmentQueryConverterFactoryLink  = $segmentQueryConverterFactoryLink;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder(Segment $segment)
    {
        /** @var EntityManager $em */
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
