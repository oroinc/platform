<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class SegmentQueryConverterFactory
{
    /** @var FunctionProviderInterface */
    protected $functionProvider;

    /** @var VirtualFieldProviderInterface */
    protected $virtualFieldProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var RestrictionBuilderInterface */
    protected $restrictionBuilder;

    /** @var VirtualRelationProviderInterface */
    protected $virtualRelationProvide;

    /**
     * @param FunctionProviderInterface        $functionProvider
     * @param VirtualFieldProviderInterface    $virtualFieldProvider
     * @param ManagerRegistry                  $doctrine
     * @param RestrictionBuilderInterface      $restrictionBuilder
     * @param VirtualRelationProviderInterface $virtualRelationProvide
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine,
        RestrictionBuilderInterface $restrictionBuilder,
        VirtualRelationProviderInterface $virtualRelationProvide
    ) {
        $this->functionProvider = $functionProvider;
        $this->virtualFieldProvider = $virtualFieldProvider;
        $this->doctrine = $doctrine;
        $this->restrictionBuilder = $restrictionBuilder;
        $this->virtualRelationProvide = $virtualRelationProvide;
    }

    /**
     * @return SegmentQueryConverter
     */
    public function createInstance()
    {
        $converter = new SegmentQueryConverter(
            $this->functionProvider,
            $this->virtualFieldProvider,
            $this->doctrine,
            $this->restrictionBuilder
        );

        $converter->setVirtualRelationProvider($this->virtualRelationProvide);

        return $converter;
    }
}
