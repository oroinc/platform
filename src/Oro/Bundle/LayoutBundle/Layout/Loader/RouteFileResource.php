<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

class RouteFileResource extends FileResource
{
    /** @var string */
    protected $routeName;

    /**
     * @param string $filename
     * @param string $routeName
     */
    public function __construct($filename, $routeName)
    {
        $this->routeName = $routeName;

        parent::__construct($filename);
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }
}
