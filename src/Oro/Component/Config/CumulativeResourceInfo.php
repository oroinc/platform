<?php

namespace Oro\Component\Config;

/**
 * Stores details for a CumulativeResource
 */
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
     * @var null|string
     */
    public $folderPlaceholder = null;

    /**
     * @param string $bundleClass
     * @param string $name
     * @param string $path
     * @param array  $data
     */
    public function __construct($bundleClass, $name, $path, array $data = [], ?string $folderPlaceholder = null)
    {
        $this->bundleClass       = $bundleClass;
        $this->name              = $name;
        $this->path              = $path;
        $this->data              = $data;
        $this->folderPlaceholder = $folderPlaceholder;
    }
}
