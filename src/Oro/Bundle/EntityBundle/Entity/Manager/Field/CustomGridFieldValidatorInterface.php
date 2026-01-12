<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager\Field;

use Oro\Bundle\EntityBundle\Exception\IncorrectEntityException;

/**
 * Defines the contract for validating custom entity fields in datagrids.
 *
 * Implementations of this interface provide validation logic to determine whether
 * a specific field on an entity can be edited in a custom grid and whether the field exists.
 */
interface CustomGridFieldValidatorInterface
{
    /**
     * @param Object $entity
     * @param string $fieldName
     *
     * @return bool
     *
     * @throws IncorrectEntityException
     */
    public function hasAccessEditField($entity, $fieldName);

    /**
     * @param Object $entity
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($entity, $fieldName);
}
