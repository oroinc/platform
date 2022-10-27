<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;

/**
 * The provider for virtual relations defined in "Resources/config/oro/entity.yml" files.
 */
class ConfigVirtualRelationProvider extends AbstractConfigVirtualProvider implements VirtualRelationProviderInterface
{
    /** @var EntityConfigurationProvider */
    private $configProvider;

    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        EntityConfigurationProvider $configProvider
    ) {
        parent::__construct($entityHierarchyProvider);
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualRelation($className, $fieldName)
    {
        $items = $this->getItems();

        return !empty($items[$className][$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        $items = $this->getItems();

        if (empty($items[$className][$fieldName]['query'])) {
            return [];
        }

        return $items[$className][$fieldName]['query'];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        $items = $this->getItems();

        if (empty($items[$className])) {
            return [];
        }

        return $items[$className];
    }

    /**
     * Return alias for single join
     * Get from option for multiple joins
     *
     * {@inheritdoc}
     */
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        if (!$this->isVirtualRelation($className, $fieldName)) {
            throw new \InvalidArgumentException(
                sprintf('Not a virtual relation "%s::%s"', $className, $fieldName)
            );
        }

        $items = $this->getItems();
        if (!empty($items[$className][$fieldName]['target_join_alias'])) {
            return $items[$className][$fieldName]['target_join_alias'];
        }

        $query = $this->getVirtualRelationQuery($className, $fieldName);
        if (!$query) {
            throw new \InvalidArgumentException(
                sprintf('Query configuration is empty for "%s::%s"', $className, $fieldName)
            );
        }

        $joins = [];
        foreach ([Join::LEFT_JOIN, Join::INNER_JOIN] as $type) {
            $type = strtolower($type);
            if (empty($query['join'][$type])) {
                continue;
            }

            $joins = array_merge($joins, $query['join'][$type]);
        }

        if (1 === count($joins)) {
            $join = reset($joins);
            if (!empty($join['alias'])) {
                return $join['alias'];
            }

            throw new \InvalidArgumentException(
                sprintf('Alias for join is not configured for "%s::%s"', $className, $fieldName)
            );
        }

        throw new \InvalidArgumentException(
            sprintf('Please configure "target_join_alias" option for "%s::%s"', $className, $fieldName)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration()
    {
        return $this->configProvider->getConfiguration(EntityConfiguration::VIRTUAL_RELATIONS);
    }
}
