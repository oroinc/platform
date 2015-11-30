<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Processor\ApiContext;

class MetadataContext extends ApiContext
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** the configuration of an entity */
    const CONFIG = 'config';

    /** additional metadata information that should be returned */
    const EXTRA = 'extra';

    public function __construct()
    {
        $this->set(self::EXTRA, []);
    }

    /**
     * Gets FQCN of an entity.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of an entity.
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * Gets the configuration of an entity.
     *
     * @return array|null
     */
    public function getConfig()
    {
        return $this->get(self::CONFIG);
    }

    /**
     * Sets the configuration of an entity.
     *
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->set(self::CONFIG, $config);
    }

    /**
     * Checks if the specified additional metadata information is requested.
     *
     * @param string $extra
     *
     * @return bool
     */
    public function hasExtra($extra)
    {
        return in_array($extra, $this->get(self::EXTRA), true);
    }

    /**
     * Gets requested additional metadata information.
     *
     * @return string[]
     */
    public function getExtras()
    {
        return $this->get(self::EXTRA);
    }

    /**
     * Sets additional metadata information that you need to be returned.
     *
     * @param string[] $extras
     */
    public function setExtras($extras)
    {
        $this->set(self::EXTRA, $extras);
    }
}
