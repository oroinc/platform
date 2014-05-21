<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\SegmentBundle\Model\RestrictionSegmentProxy;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;

class DynamicSegmentQueryBuilder implements QueryBuilderInterface
{
    /** @var RestrictionBuilder */
    protected $restrictionBuilder;

    /** @var Manager */
    protected $manager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param RestrictionBuilder            $restrictionBuilder
     * @param Manager                       $manager
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param ManagerRegistry               $doctrine
     */
    public function __construct(
        RestrictionBuilder $restrictionBuilder,
        Manager $manager,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine
    ) {
        $this->restrictionBuilder   = $restrictionBuilder;
        $this->manager              = $manager;
        $this->virtualFieldProvider = $virtualFieldProvider;
        $this->doctrine             = $doctrine;
    }

    /**
     * {inheritdoc}
     */
    public function build(Segment $segment)
    {
        $converter = new SegmentQueryConverter(
            $this->manager,
            $this->virtualFieldProvider,
            $this->doctrine,
            $this->restrictionBuilder
        );
        $qb        = $converter->convert(
            new RestrictionSegmentProxy($segment, $this->doctrine->getManagerForClass($segment->getEntity()))
        );

        return $qb->getQuery();
    }
}
