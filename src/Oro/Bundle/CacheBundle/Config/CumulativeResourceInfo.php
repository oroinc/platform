<?php

namespace Oro\Bundle\CacheBundle\Config;

class CumulativeResourceInfo
{
    /**
     * @var string
     */
    public $bundleName;

    /**
     * @var string
     */
    public $bundleClass;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $path;

    /**
     * @var array
     */
    public $data;

    /**
     * @param string $bundleName
     * @param string $bundleClass
     * @param string $name
     * @param string $path
     * @param array  $data
     */
    public function __construct($bundleName, $bundleClass, $name, $path, $data)
    {
        $this->bundleName  = $bundleName;
        $this->bundleClass = $bundleClass;
        $this->name        = $name;
        $this->path        = $path;
        $this->data        = $data;
    }
}
