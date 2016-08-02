<?php

namespace Oro\Bundle\SoapBundle\ServiceDefinition\Loader;

use BeSimple\SoapBundle\ServiceDefinition\Loader\AnnotationComplexTypeLoader as BeSimpleAnnotationComplexTypeLoader;

/**
 * Override BeSimple loader in order to provide a way
 * to dynamically filter available class properties
 *
 * Filters could be added by compiler pass,
 * by tagging their service definition with LoadPass::LOADER_FILTER_TAG
 */
class AnnotationComplexTypeLoader extends BeSimpleAnnotationComplexTypeLoader implements FilterableLoaderInterface
{
    /** @var array */
    protected $complexTypeFilters = [];

    /**
     * @param ComplexTypeFilterInterface $filter
     */
    public function addTypeFilter(ComplexTypeFilterInterface $filter)
    {
        $this->complexTypeFilters[] = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function load($class, $type = null)
    {
        $annotations = parent::load($class, $type);

        /** @var ComplexTypeFilterInterface $filter */
        foreach ($this->complexTypeFilters as $filter) {
            $annotations['properties'] = $filter->filterProperties($class, $annotations['properties']);
        }

        return $annotations;
    }
}
