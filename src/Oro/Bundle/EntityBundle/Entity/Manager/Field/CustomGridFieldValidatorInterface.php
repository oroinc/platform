<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager\Field;

use Oro\Bundle\EntityBundle\Exception\IncorrectEntityException;

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
