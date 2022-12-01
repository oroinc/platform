<?php

namespace Oro\Bundle\SearchBundle\Formatter;

/**
 * Convert decimal flat values to search index representation for ORM engine
 */
class DecimalFlatValueFormatter implements ValueFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format($value): string
    {
        // the standard implementation stores price values as is
        return (string)$value;
    }
}
