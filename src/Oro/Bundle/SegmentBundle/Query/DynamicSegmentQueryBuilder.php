<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\RestrictionSegmentProxy;

class DynamicSegmentQueryBuilder implements QueryBuilderInterface
{
    /** @var RestrictionBuilder */
    protected $restrictionBuilder;

    /** @var Manager */
    protected $manager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var VirtualFieldProviderInterface */
    protected $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface */
    protected $virtualRelationProvider;

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
     * @param VirtualRelationProviderInterface $virtualRelationProvider
     */
    public function setVirtualRelationProvider($virtualRelationProvider)
    {
        $this->virtualRelationProvider = $virtualRelationProvider;
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
        $converter = new SegmentQueryConverter(
            $this->manager,
            $this->virtualFieldProvider,
            $this->doctrine,
            $this->restrictionBuilder
        );

        if ($this->virtualRelationProvider) {
            $converter->setVirtualRelationProvider($this->virtualRelationProvider);
        }
        /** @var EntityManager  $em */
        $em = $this->doctrine->getManagerForClass($segment->getEntity());
        $qb = $converter->convert(new RestrictionSegmentProxy($segment, $em));

        return $qb;
    }
}
