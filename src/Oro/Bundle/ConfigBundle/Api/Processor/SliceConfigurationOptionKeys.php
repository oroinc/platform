<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sorts configuration option keys alphabetically and applies the pagination.
 */
class SliceConfigurationOptionKeys implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        /** @var string[] $optionKeys */
        $optionKeys = $context->get(LoadConfigurationOptionKeys::OPTION_KEYS);
        $totalCount = \count($optionKeys);
        sort($optionKeys, SORT_FLAG_CASE);

        $criteria = $context->getCriteria();
        if (null !== $criteria) {
            $maxResults = $criteria->getMaxResults();
            if (null !== $maxResults) {
                $optionKeys = \array_slice($optionKeys, $criteria->getFirstResult() ?? 0, $maxResults);
            }
        }

        $context->set(LoadConfigurationOptionKeys::OPTION_KEYS, $optionKeys);
        $context->setTotalCountCallback(function () use ($totalCount) {
            return $totalCount;
        });
    }
}
