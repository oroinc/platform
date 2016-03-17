<?php

namespace Oro\Bundle\ApiBundle\Processor;

class CustomizeLoadedDataContext extends ApiContext
{
    /** FQCN of a root entity */
    const ROOT_CLASS_NAME = 'rootClass';

    /** a path inside a root entity to a customizing entity */
    const PROPERTY_PATH = 'propertyPath';

    /** FQCN of a customizing entity */
    const CLASS_NAME = 'class';

    /**
     * Gets FQCN of a root entity.
     *
     * @return string|null
     */
    public function getRootClassName()
    {
        return $this->get(self::ROOT_CLASS_NAME);
    }

    /**
     * Sets FQCN of a root entity.
     *
     * @param string $className
     */
    public function setRootClassName($className)
    {
        $this->set(self::ROOT_CLASS_NAME, $className);
    }

    /**
     * Gets a path inside a root entity to a customizing entity.
     *
     * @return string|null
     */
    public function getPropertyPath()
    {
        return $this->get(self::PROPERTY_PATH);
    }

    /**
     * Sets a path inside a root entity to a customizing entity.
     *
     * @param string $propertyPath
     */
    public function setPropertyPath($propertyPath)
    {
        $this->set(self::PROPERTY_PATH, $propertyPath);
    }

    /**
     * Gets FQCN of a customizing entity.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of a customizing entity.
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->set(self::CLASS_NAME, $className);
    }
}
