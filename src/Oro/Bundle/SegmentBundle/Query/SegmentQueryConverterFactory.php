<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var RestrictionBuilderInterface */
    private $restrictionBuilder;

    /** @var SegmentQueryConverterState */
    private $segmentQueryConverterState;

    /**
     * @param FunctionProviderInterface        $functionProvider
     * @param VirtualFieldProviderInterface    $virtualFieldProvider
     * @param VirtualRelationProviderInterface $virtualRelationProvider
     * @param DoctrineHelper                   $doctrineHelper
     * @param RestrictionBuilderInterface      $restrictionBuilder
     * @param SegmentQueryConverterState       $segmentQueryConverterState
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider,
        DoctrineHelper $doctrineHelper,
        RestrictionBuilderInterface $restrictionBuilder,
        SegmentQueryConverterState $segmentQueryConverterState
    ) {
        $this->functionProvider = $functionProvider;
        $this->virtualFieldProvider = $virtualFieldProvider;
        $this->virtualRelationProvider = $virtualRelationProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->restrictionBuilder = $restrictionBuilder;
        $this->segmentQueryConverterState = $segmentQueryConverterState;
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
            $this->doctrineHelper,
            $this->restrictionBuilder,
            $this->segmentQueryConverterState
        );
    }
}
