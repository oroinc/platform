<?php

namespace Oro\Bundle\ActionBundle\Model;

interface EntityParameterInterface extends ParameterInterface
{
    /**
     * @param array $entityAcl
     */
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
