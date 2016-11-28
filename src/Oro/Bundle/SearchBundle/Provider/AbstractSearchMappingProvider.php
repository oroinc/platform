<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

abstract class AbstractSearchMappingProvider
{
    /**
     * @return array
     */
    abstract public function getMappingConfig();

    /**
     * Get array with entity aliases
     *
     * @return array
     *  key - entity class name
     *  value - entity search alias
     */
    public function getEntitiesListAliases()
    {
        $entities = [];

        $mappingConfig = $this->getMappingConfig();
        foreach ($mappingConfig as $class => $mappingEntity) {
            $entities[$class] = isset($mappingEntity['alias']) ? $mappingEntity['alias'] : '';
        }

        return $entities;
    }

    /**
     * Gets search aliases for entities
     *
     * @param string[] $classNames The list of entity FQCN
     *
     * @return array [entity class name => entity search alias, ...]
     *
     * @throws \InvalidArgumentException if some of requested entities is not registered in the search index
     *                                   or has no the search alias
     */
    public function getEntityAliases(array $classNames = [])
    {
        $result = [];

        $mappingConfig = $this->getMappingConfig();
        if (empty($classNames)) {
            foreach ($mappingConfig as $className => $mapping) {
                if (!empty($mapping['alias'])) {
                    $result[$className] = $mapping['alias'];
                }
            }
        } else {
            foreach ($classNames as $className) {
                if (empty($mappingConfig[$className]['alias'])) {
                    throw new \InvalidArgumentException(
                        sprintf('The search alias for the entity "%s" not found.', $className)
                    );
                }
                $result[$className] = $mappingConfig[$className]['alias'];
            }
        }

        return $result;
    }

    /**
     * Gets the search alias of a given entity
     *
     * @param string $className The FQCN of an entity
     *
     * @return string|null The search alias of the entity
     *                     or NULL if the entity is not registered in a search index or has no the search alias
     */
    public function getEntityAlias($className)
    {
        $mappingConfig = $this->getMappingConfig();

        return !empty($mappingConfig[$className]['alias'])
            ? $mappingConfig[$className]['alias']
            : null;
    }

    /**
     * Gets the FQCN of an entity by given search alias
     *
     * @param string $alias
     *
     * @return null|string
     */
    public function getEntityClass($alias)
    {
        $mappingConfig = $this->getMappingConfig();

        foreach ($mappingConfig as $className => $config) {
            if ($config['alias'] == $alias) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Get list of available entity classes
     * @throws InvalidConfigurationException
     * @return array
     */
    public function getEntityClasses()
    {
        $mappingConfig = $this->getMappingConfig();

        if (empty($mappingConfig)) {
            throw new InvalidConfigurationException('Mapping config is empty.');
        }

        return array_keys($mappingConfig);
    }

    /**
     * Return true if given class supports
     *
     * @param mixed $className
     *
     * @return bool
     */
    public function isClassSupported($className)
    {
        $mappingConfig = $this->getMappingConfig();

        return array_key_exists($className, $mappingConfig);
    }

    /**
     * Return true if fields mapping exists for the given class name
     *
     * @param mixed $className
     *
     * @return bool
     */
    public function hasFieldsMapping($className)
    {
        $mappingConfig = $this->getMappingConfig();

        return array_key_exists($className, $mappingConfig)
        && isset($mappingConfig[$className]['fields'])
        && !empty($mappingConfig[$className]['fields']);
    }

    /**
     * Get mapping parameter for entity
     *
     * @param string $entity
     * @param string $parameter
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getEntityMapParameter($entity, $parameter, $defaultValue = false)
    {
        $entityConfig = $this->getEntityConfig($entity);
        if ($entityConfig && isset($entityConfig[$parameter])) {
            return $entityConfig[$parameter];
        }

        return $defaultValue;
    }

    /**
     * Get mapping config for entity
     *
     * @param string $entity
     *
     * @return array
     */
    public function getEntityConfig($entity)
    {
        $mappingConfig = $this->getMappingConfig();
        if (isset($mappingConfig[(string)$entity])) {
            return $mappingConfig[(string)$entity];
        }

        return [];
    }
}
