<?php

namespace Oro\Component\Config;

class CumulativeResourceInfo
{
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
    public $data = [];

    /**
     * @param string $bundleClass
     * @param string $name
     * @param string $path
     * @param array  $data
     */
    public function __construct($bundleClass, $name, $path, array $data = [])
    {
        $this->bundleClass = $bundleClass;
        $this->name        = $name;
        $this->path        = $path;
        $this->data        = $data;
    }
}
