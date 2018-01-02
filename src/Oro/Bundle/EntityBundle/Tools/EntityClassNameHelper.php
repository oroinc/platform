<?php

namespace Oro\Bundle\EntityBundle\Tools;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EntityClassNameHelper
{
    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * Resolves the entity class name
     *
     * @param string $entityName    The class name, url-safe class name, alias or plural alias of the entity
     * @param bool   $isPluralAlias Determines whether the entity name may be a singular or plural alias
     *
     * @return string The FQCN of an entity
     */
    public function resolveEntityClass($entityName, $isPluralAlias = false)
    {
        if (!empty($entityName) && false === strpos($entityName, '\\')) {
            if (strtolower($entityName[0]) === $entityName[0]) {
                $entityName = $this->getEntityClassByAlias($entityName, $isPluralAlias);
            } else {
                $className = str_replace('_', '\\', $entityName);
                if (strpos($className, ExtendHelper::ENTITY_NAMESPACE) === 0) {
                    // a custom entity can contain _ in class name
                    $className = ExtendHelper::ENTITY_NAMESPACE
                        . substr($entityName, strlen(ExtendHelper::ENTITY_NAMESPACE));
                }
                $entityName = false === strpos($className, '\\')
                    ? $this->getEntityClassByAlias($className, $isPluralAlias)
                    : $className;
            }
        }

        return $entityName;
    }

    /**
     * Converts the class name to a form that can be safely used in URL
     *
     * @param string $className The class name
     *
     * @return string The URL-safe representation of a class name
     */
    public function getUrlSafeClassName($className)
    {
        return str_replace('\\', '_', $className);
    }

    /**
     * Resolves the entity class name by entity alias
     *
     * @param string $entityAlias   The alias or plural alias of the entity
     * @param bool   $isPluralAlias Determines whether the entity name may be a singular of plural alias
     *
     * @return string The FQCN of an entity
     */
    protected function getEntityClassByAlias($entityAlias, $isPluralAlias)
    {
        return $isPluralAlias
            ? $this->entityAliasResolver->getClassByPluralAlias($entityAlias)
            : $this->entityAliasResolver->getClassByAlias($entityAlias);
    }
}
