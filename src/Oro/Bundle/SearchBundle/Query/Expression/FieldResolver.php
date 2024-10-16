<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * The default implementation of FieldResolverInterface.
 */
class FieldResolver implements FieldResolverInterface
{
    #[\Override]
    public function resolveFieldName(string $fieldName): string
    {
        return $fieldName;
    }

    #[\Override]
    public function resolveFieldType(string $fieldName): string
    {
        return Query::TYPE_TEXT;
    }
}
