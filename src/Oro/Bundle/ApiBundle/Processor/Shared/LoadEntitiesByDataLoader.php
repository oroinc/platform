<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntityConfig;

/**
 * Loads entities using a specified data loader.
 */
class LoadEntitiesByDataLoader implements ProcessorInterface
{
    private DataLoaderInterface $dataLoader;
    private bool $isDataNormalized;

    public function __construct(DataLoaderInterface $dataLoader, bool $isDataNormalized = true)
    {
        $this->dataLoader = $dataLoader;
        $this->isDataNormalized = $isDataNormalized;
    }

    #[\Override]
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

        $normalizationContext = $context->getNormalizationContext();
        $maxResults = $query->getMaxResults();
        $data = $this->dataLoader->loadData($query, $config, $normalizationContext);
        if ($data) {
            $hasMore = $this->preSerializeData($data, $config, $maxResults);
            $result = $this->dataLoader->serializeData($data, $config, $normalizationContext);
            if ($hasMore) {
                $result[ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
            }
            $context->setResult($result);
        } else {
            $context->setResult([]);
        }

        if ($this->isDataNormalized) {
            // data are already normalized
            $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
        }
    }

    private function preSerializeData(array &$data, EntityConfig $config, ?int $limit): bool
    {
        $hasMore = false;
        if (null !== $limit && $config->getHasMore() && \count($data) > $limit) {
            $hasMore = true;
            $data = \array_slice($data, 0, $limit);
        }

        return $hasMore;
    }
}
