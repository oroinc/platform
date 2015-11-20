<?php

namespace Oro\Bundle\ActivityListBundle\Helper;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

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
     * for integrate activity lists from inheritance targets
     *
     * @param QueryBuilder $qb
     * @param string  $entityClass
     * @param integer $entityId
     */
    public function applyInheritanceActivity(QueryBuilder $qb, $entityClass, $entityId)
    {
        if (!$this->hasInheritances($entityClass)) {
            return;
        }
        $inheritanceTargets = $this->getInheritanceTargetsRelations($entityClass);

        foreach ($inheritanceTargets as $key => $inheritanceTarget) {
            $alias = 'ta_' . $key;
            $qb->leftJoin('activity.' . $inheritanceTarget['targetClassAlias'], $alias);

            $qb->orWhere(
                $qb->expr()->andX(
                    $qb->expr()->andX(
                        $qb->expr()->in(
                            $alias . '.id',
                            $this->getSubQuery(
                                $inheritanceTarget['targetClass'],
                                $inheritanceTarget['path'],
                                $entityId,
                                $key
                            )->getDQL()
                        )
                    )
                )
            );
        }
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    protected function getInheritanceTargetsRelations($entityClass)
    {
        $configValues = $this->configManager->getEntityConfig('activity', $entityClass)->getValues();
        $filteredTargets = $this->prepareTargetData($configValues['inheritance_targets']);

        return $filteredTargets;
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
     * @param string $target
     * @param string[] $path
     * @param integer $entityId
     * @param integer $uniqueKey
     *
     * @return QueryBuilder
     */
    protected function getSubQuery($target, $path, $entityId, $uniqueKey)
    {
        $alias = 'inherit_' . $uniqueKey;
        $subQueryBuilder = $this->registry->getManagerForClass($target)->createQueryBuilder();
        $subQueryBuilder->select($alias . '.id')->from($target, $alias);

        foreach ($path as $key => $field) {
            $newAlias = 't_' . $uniqueKey . '_' . $key;
            $subQueryBuilder->join($alias . '.' . $field, $newAlias);
            $alias = $newAlias;
        }

        $subQueryBuilder
            ->where($alias . '.id = :entityId')
            ->setParameter('entityId', $entityId);

        return $subQueryBuilder;
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
