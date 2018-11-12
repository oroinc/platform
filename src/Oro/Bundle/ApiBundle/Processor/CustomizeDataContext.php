<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * The base execution context for processors for "customize_loaded_data" and "customize_form_data" actions.
 */
abstract class CustomizeDataContext extends ApiContext
{
    /** FQCN of a root entity */
    const ROOT_CLASS_NAME = 'rootClass';

    /** a path inside a root entity to a customizing entity */
    const PROPERTY_PATH = 'propertyPath';

    /** FQCN of a customizing entity */
    const CLASS_NAME = 'class';

    /** @var EntityDefinitionConfig|null */
    private $rootConfig;

    /** @var EntityDefinitionConfig|null */
    private $config;

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
        return $this->rootConfig;
    }

    /**
     * Sets a configuration of a root entity.
     *
     * @param EntityDefinitionConfig|null $config
     */
    public function setRootConfig(EntityDefinitionConfig $config = null)
    {
        $this->rootConfig = $config;
    }

    /**
     * Gets a configuration of a customizing entity.
     *
     * @return EntityDefinitionConfig|null
     */
    public function getConfig()
    {
        return $this->config;
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
