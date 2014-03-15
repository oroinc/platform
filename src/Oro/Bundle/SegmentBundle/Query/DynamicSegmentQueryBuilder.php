<?php

namespace Oro\Bundle\SegmentBundle\Query;

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
     * @param RestrictionBuilder $restrictionBuilder
     * @param Manager            $manager
     * @param ManagerRegistry    $doctrine
     */
    public function __construct(
        RestrictionBuilder $restrictionBuilder,
        Manager $manager,
        ManagerRegistry $doctrine
    ) {
        $this->restrictionBuilder = $restrictionBuilder;
        $this->manager            = $manager;
        $this->doctrine           = $doctrine;
    }

    /**
     * {inheritdoc}
     */
    public function build(Segment $segment)
    {
        $converter = new SegmentQueryConverter($this->manager, $this->doctrine, $this->restrictionBuilder);
        $qb        = $converter->convert(
            new RestrictionSegmentProxy($segment, $this->doctrine->getManagerForClass($segment->getEntity()))
        );

        return $qb->getQuery();
    }
}
