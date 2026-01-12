<?php

namespace Oro\Bundle\ActionBundle\Model;

/**
 * Defines the contract for entity parameters with ACL and property path information.
 */
interface EntityParameterInterface extends ParameterInterface
{
    public function setEntityAcl(array $entityAcl);

    /**
     * @return bool
     */
    public function isEntityUpdateAllowed();

    /**
     * @return bool
     */
    public function isEntityDeleteAllowed();

    /**
     * @return string
     */
    public function getPropertyPath();

    /**
     * @param string $propertyPath
     * @return ParameterInterface
     */
    public function setPropertyPath($propertyPath);
}
