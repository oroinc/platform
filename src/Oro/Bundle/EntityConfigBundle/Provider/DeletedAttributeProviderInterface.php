<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Defines the contract for accessing and managing deleted attributes.
 *
 * This interface extends {@see AttributeValueProviderInterface} to provide methods for retrieving deleted attribute
 * field configuration models by their identifiers, enabling operations on attributes that have been removed
 * from the system.
 */
interface DeletedAttributeProviderInterface extends AttributeValueProviderInterface
{
    /**
     * @param array $ids
     * @return FieldConfigModel[]
     */
    public function getAttributesByIds(array $ids);
}
