<?php

namespace Oro\Bundle\EntityBundle\Provider;

interface VirtualRelationProviderInterface
{
    /**
     * Indicates whether the given field is a virtual relation or not
     *
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    public function isVirtualRelation($className, $fieldName);

    /**
     * Gets a query associated with the given virtual field
     *
     * @param string $className
     * @param string $fieldName
     * @return array
     */
    public function getVirtualRelationQuery($className, $fieldName);

    /**
     * Gets virtual relations field names for given class
     *
     * @param string $className
     * @return array
     */
    public function getVirtualRelations($className);

    /**
     * Gets a target alias
     *
     * @param string      $className
     * @param string      $fieldName
     * @param string|null $selectFieldName
     * @return string
     */
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null);
}
