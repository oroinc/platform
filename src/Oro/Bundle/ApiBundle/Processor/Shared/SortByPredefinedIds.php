<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sorts the result in the same order as the order of identifiers
 * stored in the "_sorted_ids" item in the context.
 * This processor can be useful in case entity IDs are loaded from one source, e.g. by the search index,
 * but other entity properties are loaded from another source by these IDs, e.g. from the database.
 */
class SortByPredefinedIds implements ProcessorInterface
{
    public const SORTED_IDS = '_sorted_ids';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        $sortedIds = $context->get(self::SORTED_IDS);
        if (empty($sortedIds)) {
            return;
        }

        $data = $context->getResult();
        if (\is_array($data)) {
            $context->setResult($this->sortData($data, $sortedIds));
        }
    }

    private function sortData(array $data, array $sortedIds): array
    {
        $map = [];
        foreach ($data as $key => $val) {
            $map[$val['id']] = $key;
        }

        $sortedData = [];
        foreach ($sortedIds as $id) {
            if (isset($map[$id])) {
                $sortedData[] = $data[$map[$id]];
            }
        }

        return $sortedData;
    }
}
