<?php

namespace Oro\Component\Config;

class CumulativeResourceManager
{
    /**
     * The singleton instance
     *
     * @var CumulativeResourceManager
     */
    private static $instance;

    /**
     * @var string
     */
    private $appRootDir;

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
        $this->appRootDir = null;
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

    /**
     * Gets application root directory
     *
     * @return string
     */
    public function getAppRootDir()
    {
        return $this->appRootDir;
    }

    /**
     * Sets application root directory
     *
     * @param string $appRootDir
     * @return CumulativeResourceManager
     */
    public function setAppRootDir($appRootDir)
    {
        $this->appRootDir = $appRootDir;

        return $this;
    }
}
