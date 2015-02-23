<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

class LayoutUpdateGenerator
{
    const CLASS_PREFIX = '__Oro_Layout_Update_';

    /** @var bool */
    protected $debug;

    /** @var string */
    protected $cache;

    /**
     * @param bool   $debug
     * @param string $cache
     */
    public function __construct($debug, $cache)
    {
        $this->debug = $debug;
        $this->cache = $cache;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function generateClassName($name)
    {
        return static::CLASS_PREFIX . hash('sha256', $this->getLoader()->getCacheKey($name));
    }

    /**
     * @param string $name
     *
     * @return bool|string
     */
    public function getCacheFilename($name)
    {
        if ($this->debug) {
            return false;
        }

        $class = substr($this->generateClassName($name), strlen(static::CLASS_PREFIX));

        return sprintf('%s/%s/%s/%s.php', $this->cache, substr($class, 0, 2), substr($class, 2, 2), substr($class, 4));
    }

    public function generate($name)
    {
        return '';
    }
}
