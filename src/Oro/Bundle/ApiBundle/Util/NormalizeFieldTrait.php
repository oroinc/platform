<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Collection\Criteria;

trait NormalizeFieldTrait
{
    /**
     * @param string $field
     * @param array  $placeholders
     *
     * @return string
     */
    protected function normalizeField($field, array $placeholders)
    {
        $normalizedField = null;
        if (0 !== strpos($field, '{')) {
            $lastDelimiter = strrpos($field, '.');
            if (false !== $lastDelimiter) {
                $path = sprintf(Criteria::PLACEHOLDER_TEMPLATE, substr($field, 0, $lastDelimiter));
                if (isset($placeholders[$path])) {
                    $field = $placeholders[$path] . substr($field, $lastDelimiter);
                }
            }
        }
        if (null === $normalizedField) {
            $normalizedField = strtr($field, $placeholders);
        }

        return $normalizedField;
    }
}
