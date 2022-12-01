<?php

namespace Oro\Bundle\SearchBundle\Formatter;

/**
 * Formatter interface that converts values to search index representation
 */
interface ValueFormatterInterface
{
    /**
     * @param mixed $value
     * @return string
     */
    public function format($value) : string;
}
