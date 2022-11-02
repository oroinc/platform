<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * The default implementation of FieldResolverInterface.
 */
class FieldResolver implements FieldResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveFieldName(string $fieldName): string
    {
        return $fieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveFieldType(string $fieldName): string
    {
        return Query::TYPE_TEXT;
    }
}
