<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;

/**
 * The factory to create the segment query converter.
 */
class SegmentQueryConverterFactory
{
    /** @var FunctionProviderInterface */
    private $functionProvider;

    /** @var VirtualFieldProviderInterface */
    private $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface */
    private $virtualRelationProvider;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var RestrictionBuilderInterface */
    private $restrictionBuilder;

    /**
     * @param FunctionProviderInterface        $functionProvider
     * @param VirtualFieldProviderInterface    $virtualFieldProvider
     * @param VirtualRelationProviderInterface $virtualRelationProvider
     * @param ManagerRegistry                  $doctrine
     * @param RestrictionBuilderInterface      $restrictionBuilder
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider,
        ManagerRegistry $doctrine,
        RestrictionBuilderInterface $restrictionBuilder
    ) {
        $this->functionProvider = $functionProvider;
        $this->virtualFieldProvider = $virtualFieldProvider;
        $this->virtualRelationProvider = $virtualRelationProvider;
        $this->doctrine = $doctrine;
        $this->restrictionBuilder = $restrictionBuilder;
    }

    /**
     * @return SegmentQueryConverter
     */
    public function createInstance(): SegmentQueryConverter
    {
        return new SegmentQueryConverter(
            $this->functionProvider,
            $this->virtualFieldProvider,
            $this->virtualRelationProvider,
            $this->doctrine,
            $this->restrictionBuilder
        );
    }
}
