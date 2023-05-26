<?php

namespace Oro\Bundle\TranslationBundle\Api\Repository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\DataLoaderInterface;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * The service to load translations.
 */
class TranslationDataLoader implements DataLoaderInterface
{
    private EntitySerializer $entitySerializer;
    private TranslationQueryModifier $queryModifier;

    public function __construct(EntitySerializer $entitySerializer, TranslationQueryModifier $queryModifier)
    {
        $this->entitySerializer = $entitySerializer;
        $this->queryModifier = $queryModifier;
    }

    /**
     * {@inheritDoc}
     */
    public function loadData(QueryBuilder $qb, EntityDefinitionConfig $config, array $context): array
    {
        $preparedQuery = $this->entitySerializer->buildQuery(
            $qb,
            $config,
            $context,
            function (QueryBuilder $qb, EntityConfig $config) {
                $this->queryModifier->updateQuery($qb, $config);
            }
        );

        $data = $preparedQuery->getArrayResult();
        $this->normalizeData($data);

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeData(array $data, EntityDefinitionConfig $config, array $context): array
    {
        return $this->entitySerializer->serializeEntities($data, TranslationKey::class, $config, $context);
    }

    private function normalizeData(array &$data): void
    {
        foreach ($data as &$item) {
            if (\array_key_exists(0, $item)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $item = array_merge($item, $item[0]);
                unset($item[0]);
            }
        }
    }
}
