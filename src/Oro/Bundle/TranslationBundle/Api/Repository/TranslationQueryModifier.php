<?php

namespace Oro\Bundle\TranslationBundle\Api\Repository;

use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\EntitySerializer\EntityConfig;

/**
 * Prepares ORM query to load translations.
 */
class TranslationQueryModifier
{
    public function updateQuery(QueryBuilder $qb, EntityConfig $config): void
    {
        $languageJoinOptimizer = new TranslationLanguageJoinOptimizer();
        $languageJoinOptimizer->updateQuery($qb);
        $languageCode = $languageJoinOptimizer->getLanguageCode();
        $this->addComputedFields($qb, $config, $languageCode);
        if (null === $languageCode) {
            $this->updateOrderById($qb);
        }
    }

    private function addComputedFields(QueryBuilder $qb, EntityConfig $config, ?string $languageCode): void
    {
        if (!$config->getField('languageCode')->isExcluded()) {
            if ($languageCode) {
                $qb->addSelect(sprintf('\'%s\' AS languageCode', $languageCode));
            } elseif ($this->hasJoin($qb, 'language')) {
                $qb->addSelect('language.code AS languageCode');
            }
        }
        if (!$config->getField('translationId')->isExcluded()) {
            $qb->addSelect('translation.id AS translationId');
        }
        if (!$config->getField('translatedValue')->isExcluded()) {
            $qb->addSelect('translation.value AS translatedValue');
        }
        if ($this->hasJoin($qb, 'translation')) {
            $qb->addSelect('(CASE WHEN translation.id IS NULL THEN false ELSE true END) AS hasTranslation');
        }
    }

    private function updateOrderById(QueryBuilder $qb): void
    {
        /** @var OrderBy[] $orderByPart */
        $orderByPart = $qb->getDQLPart('orderBy');
        if (!$orderByPart) {
            return;
        }

        $qb->resetDQLPart('orderBy');
        foreach ($orderByPart as $orderBy) {
            if ($this->hasSortByField($orderBy, 'e.id')) {
                $newOrderBy = new OrderBy();
                foreach ($orderBy->getParts() as $part) {
                    [$sort, $order] = explode(' ', $part, 2);
                    $newOrderBy->add($sort, $order);
                    if ('e.id' === $sort) {
                        $newOrderBy->add('language.code', $order);
                    }
                }
                $orderBy = $newOrderBy;
            }
            $qb->addOrderBy($orderBy);
        }
    }

    private function hasSortByField(OrderBy $orderBy, string $field): bool
    {
        foreach ($orderBy->getParts() as $part) {
            if (str_starts_with($part, $field . ' ')) {
                return true;
            }
        }

        return false;
    }

    private function hasJoin(QueryBuilder $qb, string $alias): bool
    {
        return null !== QueryBuilderUtil::findJoinByAlias($qb, $alias);
    }
}
