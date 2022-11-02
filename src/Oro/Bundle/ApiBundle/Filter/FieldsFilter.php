<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A filter that can be used to filter entity fields that should be returned.
 * @see \Oro\Bundle\ApiBundle\Filter\FilterNames::getFieldsFilterGroupName
 * @see \Oro\Bundle\ApiBundle\Filter\FilterNames::getFieldsFilterTemplate
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\AddFieldsFilter
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleFieldsFilter
 * @see \Oro\Bundle\ApiBundle\Processor\GetConfig\FilterFieldsByExtra
 */
class FieldsFilter extends StandaloneFilterWithDefaultValue implements SpecialHandlingFilterInterface
{
}
