<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A filter that can be used to add a list of related entities to the result.
 * @see \Oro\Bundle\ApiBundle\Filter\FilterNames::getIncludeFilterName
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\AddIncludeFilter
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleIncludeFilter
 * @see \Oro\Bundle\ApiBundle\Processor\GetConfig\ExpandRelatedEntities
 */
class IncludeFilter extends StandaloneFilter implements SpecialHandlingFilterInterface
{
}
