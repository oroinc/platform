<?php

namespace Oro\Bundle\ActivityListBundle\Helper;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class ActivityInheritanceTargetsHelper
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var Registry */
    protected $registry;

    /**
     * @param ConfigManager $configManager
     * @param Registry $registry
     */
    public function __construct(ConfigManager $configManager, Registry $registry)
    {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }

    /**
     * Check exists inheritance targets by target entity
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function hasInheritances($entityClass)
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
     *
     * @param QueryBuilder $qb
     * @param array        $inheritanceTarget
     * @param string       $aliasSuffix
     * @param string       $entityIdExpr
     * @param bool         $head Head activity only
     */
    public function applyInheritanceActivity(QueryBuilder $qb, $inheritanceTarget, $aliasSuffix, $entityIdExpr, $head)
    {
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
        if ($head) {
            $qb->andWhere($qb->expr()->andX('activity.head = true'));
        }
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    public function getInheritanceTargetsRelations($entityClass)
    {
        $filteredTargets = [];

        $configValues = $this->configManager->getEntityConfig('activity', $entityClass)->getValues();
        if (isset($configValues['inheritance_targets'])) {
            $filteredTargets = $this->prepareTargetData($configValues['inheritance_targets']);
        }

        return $filteredTargets;
    }

    /**
     * @param  string $entityClass
     * @return array
     */
    public function getInheritanceTargets($entityClass)
    {
        $configValues = $this->configManager->getEntityConfig('activity', $entityClass)->getValues();
        if ($this->hasValueInInheritanceTargets($configValues)) {
            return $configValues['inheritance_targets'];
        }

        return [];
    }

    /**
     * @param string   $target
     * @param string[] $path
     * @param string   $entityIdExpr
     * @param string  $uniqueKey
     *
     * @return QueryBuilder
     */
    protected function getSubQuery($target, $path, $entityIdExpr, $uniqueKey)
    {
        QueryBuilderUtil::checkIdentifier($uniqueKey);
        $alias = 'inherit_' . $uniqueKey;

        /** @var QueryBuilder $subQueryBuilder */
        $subQueryBuilder = $this->registry->getManagerForClass($target)->createQueryBuilder();
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

        return $subQueryBuilder;
    }

    /**
     * Get Association name
     *
     * @param string $className
     *
     * @return string
     */
    protected function getAssociationName($className)
    {
        return ExtendHelper::buildAssociationName(
            $className,
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );
    }

    /**
     * @param array $inheritanceTargets
     *
     * @return array
     */
    protected function prepareTargetData($inheritanceTargets)
    {
        $filteredTargets = [];
        foreach ($inheritanceTargets as $value) {
            if ($this->hasConfigForInheritanceTarget($value)) {
                $configTarget = $this->getConfigForClass($value['target']);
                if (array_key_exists('activities', $configTarget)) {
                    $item['targetClass'] = $value['target'];
                    $item['targetClassAlias'] = $this->getAssociationName($value['target']);
                    $item['path'] = $value['path'];
                    $filteredTargets[] = $item;
                }
            }
        }

        return $filteredTargets;
    }

    /**
     * @param $configValues
     *
     * @return bool
     */
    protected function hasValueInInheritanceTargets($configValues)
    {
        return is_array($configValues) && array_key_exists('inheritance_targets', $configValues)
        && is_array($configValues['inheritance_targets']);
    }

    /**
     * @param $className
     *
     * @return array
     */
    protected function getConfigForClass($className)
    {
        return $this
            ->configManager
            ->getEntityConfig('activity', $className)
            ->getValues();
    }

    /**
     * @param $value
     *
     * @return bool
     */
    protected function hasConfigForInheritanceTarget($value)
    {
        $result = is_array($value)
            && array_key_exists('target', $value)
            && $this->configManager->hasConfigEntityModel($value['target']);

        return $result;
    }
}
