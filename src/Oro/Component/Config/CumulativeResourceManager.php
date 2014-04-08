<?php

namespace Oro\Component\Config;

class CumulativeResourceManager
{
    /**
     * The singleton instance
     *
     * @var CumulativeResourceManager
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $bundles = [];

    /**
     * Returns the singleton instance
     *
     * @return CumulativeResourceManager
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * A private constructor to prevent create an instance of this class explicitly
     */
    private function __construct()
    {
    }

    /**
     * Clears state of this manager
     *
     * @return CumulativeResourceManager
     */
    public function clear()
    {
        $this->bundles = [];

        return $this;
    }

    /**
     * Gets list of available bundles
     *
     * @return array
     */
    public function getBundles()
    {
        return $this->bundles;
    }

    /**
     * Sets list of available bundles
     *
     * @param array $bundles
     * @return CumulativeResourceManager
     */
    public function setBundles($bundles)
    {
        $this->bundles = $bundles;

        return $this;
    }
}
