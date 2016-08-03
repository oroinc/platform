<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CustomizeLoadedDataContext extends ApiContext
{
    /** FQCN of a root entity */
    const ROOT_CLASS_NAME = 'rootClass';

    /** a path inside a root entity to a customizing entity */
    const PROPERTY_PATH = 'propertyPath';

    /** FQCN of a customizing entity */
    const CLASS_NAME = 'class';

    /** @var EntityDefinitionConfig */
    protected $config;

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

    /**
     * Gets a configuration of a root entity.
     *
     * @return EntityDefinitionConfig|null
     */
    public function getRootConfig()
    {
        return $this->getPropertyPath()
            ? $this->config
            : null;
    }

    /**
     * Gets a configuration of a customizing entity.
     *
     * @return EntityDefinitionConfig|null
     */
    public function getConfig()
    {
        $config = $this->config;
        if (null !== $config) {
            $propertyPath = $this->getPropertyPath();
            if ($propertyPath) {
                $path = ConfigUtil::explodePropertyPath($propertyPath);
                foreach ($path as $fieldName) {
                    $fieldConfig = $config->getField($fieldName);
                    $config = null !== $fieldConfig
                        ? $fieldConfig->getTargetEntity()
                        : null;
                    if (null === $config) {
                        break;
                    }
                }
            }
        }

        return $config;
    }

    /**
     * Sets a configuration of a customizing entity.
     *
     * @param EntityDefinitionConfig|null $config
     */
    public function setConfig(EntityDefinitionConfig $config = null)
    {
        $this->config = $config;
    }
}
