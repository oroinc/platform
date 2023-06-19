<?php

namespace Oro\Bundle\TranslationBundle\Api\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\TranslationBundle\Api\PredefinedLanguageCodeResolverRegistry;

/**
 * The factory to create a filter by the language code.
 */
class LanguageCodeFilterFactory
{
    private PredefinedLanguageCodeResolverRegistry $predefinedLanguageCodeResolverRegistry;

    public function __construct(PredefinedLanguageCodeResolverRegistry $predefinedLanguageCodeResolverRegistry)
    {
        $this->predefinedLanguageCodeResolverRegistry = $predefinedLanguageCodeResolverRegistry;
    }

    public function createFilter(string $dataType): ComparisonFilter
    {
        if (DataType::STRING !== $dataType) {
            throw new \LogicException(sprintf(
                'The data type for the filter by language code must be "%s", given "%s".',
                DataType::STRING,
                $dataType
            ));
        }

        $filter = new ComparisonFilter($dataType);
        $filter->setValueTransformer(function (mixed $value): mixed {
            if (\is_string($value)) {
                $resolvedValue = $this->predefinedLanguageCodeResolverRegistry->resolve($value);
                if (null !== $resolvedValue) {
                    $value = $resolvedValue;
                }
            }

            return $value;
        });

        return $filter;
    }
}
