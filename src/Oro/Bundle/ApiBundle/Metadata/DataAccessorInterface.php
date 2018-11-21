<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides an interface for classes that can extract data for meta properties and links.
 */
interface DataAccessorInterface
{
    /** The property name that can be used to get the path to an entity or an association */
    public const PATH = '__path__';
    /** The property name that can be used to get an entity type */
    public const ENTITY_TYPE = '__type__';
    /** The property name that can be used to get an entity class */
    public const ENTITY_CLASS = ConfigUtil::CLASS_NAME;
    /** The property name that can be used to get the identifier of an entity or an association */
    public const ENTITY_ID = '__id__';
    /** The property path that can be used to get the type of an entity to which an association belongs */
    public const OWNER_ENTITY_TYPE = '_.__type__';
    /** The property path that can be used to get the identifier of an entity to which an association belongs */
    public const OWNER_ENTITY_ID = '_.__id__';

    /**
     * Attempts to get a value from a context to which a caller belongs to.
     * E.g. if a meta property is defined for an entity the context is the entity data,
     * if a meta property is defined for an association the context is the data
     * of the association and an entity this association is belongs to.
     *
     * @param string $propertyPath The property path.
     *                             It can starts with "_." to get access to an entity data
     *                             in case if this method is called in the context of an association.
     * @param mixed  $value        Contains a value extracted by the specified property path.
     *                             A value of this variable is set to NULL if the operation failed.
     *
     * @return bool TRUE if a value is got; otherwise, FALSE.
     */
    public function tryGetValue(string $propertyPath, &$value): bool;
}
