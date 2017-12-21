<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidFiltersException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FiltersParserContext;

/**
 * Purifies segment filters from definition skipping incomplete parts (i.e. filters and groups).
 */
class SegmentFiltersPurifier
{
    /**
     * @param array $filters
     * @return array
     */
    public function purifyFilters(array $filters): array
    {
        return $this->purify($filters, new FiltersParserContext());
    }

    /**
     * @param array $filters
     * @param FiltersParserContext $context
     * @return array
     */
    private function purify(array $filters, FiltersParserContext $context): array
    {
        $context->checkBeginGroup();
        $context->setLastTokenType(FiltersParserContext::BEGIN_GROUP_TOKEN);

        $resultFilters = [];
        foreach ($filters as $token) {
            if (is_string($token)) {
                $this->handleOperatorToken($token, $context, $resultFilters);
            } else {
                if (isset($token['criterion'])) {
                    $this->handleFilterToken($token, $context, $resultFilters);
                } else {
                    $this->handleFilterGroupToken($token, $context, $resultFilters);
                }
            }
        }

        if ($context->getLastTokenType() === FiltersParserContext::OPERATOR_TOKEN) {
            array_pop($resultFilters);
            $context->setLastTokenType(FiltersParserContext::FILTER_TOKEN);
        }

        if (!empty($resultFilters)) {
            $context->checkEndGroup();
        }

        return $resultFilters;
    }

    /**
     * @param array $token
     * @param FiltersParserContext $context
     * @param array $resultFilters
     */
    private function handleFilterGroupToken(array $token, FiltersParserContext $context, array &$resultFilters)
    {
        if (!empty($token)) {
            $filtersGroup = $this->purify($token, new FiltersParserContext());
            if (!empty($filtersGroup)) {
                $resultFilters[] = $filtersGroup;
                $context->setLastTokenType(FiltersParserContext::FILTER_TOKEN);
            }
        }
    }

    /**
     * @param array $token
     * @param FiltersParserContext $context
     * @param array $resultFilters
     */
    private function handleFilterToken(array $token, FiltersParserContext $context, array &$resultFilters)
    {
        try {
            $context->checkFilter($token);
            $context->setLastTokenType(FiltersParserContext::FILTER_TOKEN);
            $resultFilters[] = $token;
        } catch (InvalidFiltersException $exception) {
        }
    }

    /**
     * @param string $token
     * @param FiltersParserContext $context
     * @param array $resultFilters
     */
    private function handleOperatorToken(string $token, FiltersParserContext $context, array &$resultFilters)
    {
        if ($context->getLastTokenType() === FiltersParserContext::OPERATOR_TOKEN) {
            array_pop($resultFilters);
            $resultFilters[] = $token;

            return;
        }

        try {
            $context->checkOperator($token);
            $resultFilters[] = $token;
            $context->setLastTokenType(FiltersParserContext::OPERATOR_TOKEN);
        } catch (InvalidFiltersException $exception) {
        }
    }
}
