<?php

namespace Oro\Bundle\ActivityListBundle\Helper;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SubQueryLimitHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * A set of utility methods to operate with inheritance of activity lists.
 * The inheritance of activity lists is a logical relation between different types of entities.
 * Using this logical relation it is possible to get activities related to all linked entities.
 */
class ActivityInheritanceTargetsHelper
{
    private const MAX_INHERITANCE_TARGETS = 10000;

    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;
    private SubQueryLimitHelper $limitHelper;

    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        SubQueryLimitHelper $limitHelper
    ) {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
        $this->limitHelper = $limitHelper;
    }

    /**
     * Check exists inheritance targets by target entity
     */
    public function hasInheritances(string $entityClass): bool
    {
        if ($this->configManager->hasConfigEntityModel($entityClass)) {
            $configValues = $this->getConfigForClass($entityClass);
            if ($this->hasValueInInheritanceTargets($configValues)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply to given query builder object additional conditions
     * for integrate activity lists from inheritance target
     */
    public function applyInheritanceActivity(
        QueryBuilder $qb,
        array $inheritanceTarget,
        string $aliasSuffix,
        string $entityIdExpr
    ): void {
        QueryBuilderUtil::checkIdentifier($aliasSuffix);
        $alias = 'ta_' . $aliasSuffix;
        $qb->leftJoin(QueryBuilderUtil::getField('activity', $inheritanceTarget['targetClassAlias']), $alias);
        $qb->andWhere($qb->expr()->in(
            $alias . '.id',
            $this->getSubQuery(
                $inheritanceTarget['targetClass'],
                $inheritanceTarget['path'],
                $entityIdExpr,
                $aliasSuffix
            )->getDQL()
        ));
    }

    public function getInheritanceTargetsRelations(string $entityClass): array
    {
        $filteredTargets = [];

        $configValues = $this->configManager->getEntityConfig('activity', $entityClass)->getValues();
        if (isset($configValues['inheritance_targets'])) {
            $filteredTargets = $this->prepareTargetData($configValues['inheritance_targets']);
        }

        return $filteredTargets;
    }

    public function getInheritanceTargets(string $entityClass): array
    {
        $configValues = $this->configManager->getEntityConfig('activity', $entityClass)->getValues();
        if ($this->hasValueInInheritanceTargets($configValues)) {
            return $configValues['inheritance_targets'];
        }

        return [];
    }

    private function getSubQuery(
        string $target,
        array $path,
        string $entityIdExpr,
        string $uniqueKey
    ): QueryBuilder {
        QueryBuilderUtil::checkIdentifier($uniqueKey);
        $alias = 'inherit_' . $uniqueKey;

        /** @var QueryBuilder $subQueryBuilder */
        $subQueryBuilder = $this->doctrine->getManagerForClass($target)->createQueryBuilder();
        $subQueryBuilder->select($alias . '.id')->from($target, $alias);

        foreach ($path as $key => $field) {
            QueryBuilderUtil::checkIdentifier($key);
            $newAlias = 't_' . $uniqueKey . '_' . $key;
            if (\is_array($field)) {
                $subQueryBuilder->join(
                    $field['join'],
                    $newAlias,
                    $field['conditionType'],
                    $subQueryBuilder->expr()->eq(QueryBuilderUtil::getField($newAlias, $field['field']), $alias)
                );
            } else {
                $subQueryBuilder->join(QueryBuilderUtil::getField($alias, $field), $newAlias);
            }
            $alias = $newAlias;
        }

        $subQueryBuilder->where($subQueryBuilder->expr()->eq(QueryBuilderUtil::getField($alias, 'id'), $entityIdExpr));

        $this->limitHelper->setLimit(
            $subQueryBuilder,
            static::MAX_INHERITANCE_TARGETS,
            'id'
        );

        return $subQueryBuilder;
    }

    private function getAssociationName(string $className): string
    {
        return ExtendHelper::buildAssociationName(
            $className,
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );
    }

    private function prepareTargetData(array $inheritanceTargets): array
    {
        $filteredTargets = [];
        foreach ($inheritanceTargets as $value) {
            if ($this->hasConfigForInheritanceTarget($value)) {
                $configTarget = $this->getConfigForClass($value['target']);
                if (\array_key_exists('activities', $configTarget)) {
                    $item['targetClass'] = $value['target'];
                    $item['targetClassAlias'] = $this->getAssociationName($value['target']);
                    $item['path'] = $value['path'];
                    $filteredTargets[] = $item;
                }
            }
        }

        return $filteredTargets;
    }

    private function hasValueInInheritanceTargets(mixed $configValues): bool
    {
        return
            \is_array($configValues)
            && \array_key_exists('inheritance_targets', $configValues)
            && \is_array($configValues['inheritance_targets']);
    }

    private function getConfigForClass(string $className): ?array
    {
        return $this->configManager
            ->getEntityConfig('activity', $className)
            ->getValues();
    }

    private function hasConfigForInheritanceTarget(mixed $value): bool
    {
        return
            \is_array($value)
            && \array_key_exists('target', $value)
            && $this->configManager->hasConfigEntityModel($value['target']);
    }
}
