<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\ConfigUtil;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Loads entities using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadEntitiesByEntitySerializer implements ProcessorInterface
{
    public const ENTITY_IDS = 'entity_ids';

    private EntitySerializer $entitySerializer;

    public function __construct(EntitySerializer $entitySerializer)
    {
        $this->entitySerializer = $entitySerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // only configured API resources are supported
            return;
        }

        if ($context->has(self::ENTITY_IDS)) {
            $idFieldNames = $config->getIdentifierFieldNames();
            if (\count($idFieldNames) !== 1) {
                throw new RuntimeException('The entity must have one identifier field.');
            }
            $entityIds = $context->get(self::ENTITY_IDS);
            $hasMore = $config->getHasMore();
            $maxResults = $query->getMaxResults();
            $config->setHasMore(false);
            $query->setMaxResults(\count($entityIds));
            try {
                $data = $this->sortByIds(
                    $this->entitySerializer->serialize($query, $config, $context->getNormalizationContext()),
                    $entityIds,
                    reset($idFieldNames)
                );
            } finally {
                $config->setHasMore($hasMore);
                $query->setMaxResults($maxResults);
            }
            if ($hasMore) {
                $limit = $query->getMaxResults();
                if (null !== $limit && \count($data) > $limit) {
                    $data = \array_slice($data, 0, $limit);
                    $data[ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
                }
            }
        } else {
            $data = $this->entitySerializer->serialize($query, $config, $context->getNormalizationContext());
        }
        $context->setResult($data);

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }

    public function sortByIds(array $data, array $ids, string $idFieldName): array
    {
        $dataMap = [];
        foreach ($data as $item) {
            $dataMap[$item[$idFieldName]] = $item;
        }

        $sortedData = [];
        foreach ($ids as $id) {
            if (isset($dataMap[$id])) {
                $sortedData[] = $dataMap[$id];
            }
        }

        return $sortedData;
    }
}
