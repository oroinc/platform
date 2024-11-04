<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A filter that can be used to filter data by a string field value.
 */
class StringComparisonFilter extends ComparisonFilter
{
    private bool $allowEmpty = false;

    /**
     * Indicates whether an empty value(s) is allowed.
     * An empty value is a value contains only space symbol(s).
     */
    public function isAllowEmpty(): bool
    {
        return $this->allowEmpty;
    }

    /**
     * Sets a flag indicates whether an empty value(s) is allowed.
     */
    public function setAllowEmpty(bool $allowEmpty): void
    {
        $this->allowEmpty = $allowEmpty;
    }

    #[\Override]
    public function getValueNormalizationOptions(): array
    {
        $options = parent::getValueNormalizationOptions();
        if ($this->isAllowEmpty()) {
            $options['allow_empty'] = true;
        }

        return $options;
    }
}
