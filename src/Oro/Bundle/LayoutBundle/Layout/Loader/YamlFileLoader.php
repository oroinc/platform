<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Bundle\LayoutBundle\Layout\Generator\LayoutUpdateGenerator;

class YamlFileLoader implements FileLoaderInterface
{
    /** @var array */
    protected $loaded = [];

    /** @var LayoutUpdateGenerator */
    protected $generator;

    /**
     * @param LayoutUpdateGenerator $generator
     */
    public function __construct(LayoutUpdateGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource)
    {
        return $this->doLoad($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * @param string $name
     *
     * @return LayoutUpdateInterface
     */
    protected function doLoad($name)
    {
        $className = $this->generator->generateClassName($name);

        if (isset($this->loaded[$className])) {
            return $this->loaded[$className];
        }

        if (!class_exists($className, false)) {
            if (false === $cache = $this->generator->getCacheFilename($name)) {
                eval('?>' . $this->generator->generate($name));
            } else {
                if (!is_file($cache)) {
                    $this->writeCacheFile($cache, $this->generator->generate($name));
                }

                require_once $cache;
            }
        }

        return $this->loaded[$className] = new $className($this);
    }

    /**
     * @param string $file
     * @param string $content
     */
    protected function writeCacheFile($file, $content)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to create the cache directory (%s).', $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(sprintf('Unable to write in the cache directory (%s).', $dir));
        }

        $tmpFile = tempnam($dir, basename($file));
        if (false !== @file_put_contents($tmpFile, $content)) {
            // rename does not work on Win32 before 5.2.6
            if (@rename($tmpFile, $file) || (@copy($tmpFile, $file) && unlink($tmpFile))) {
                @chmod($file, 0666 & ~umask());

                return;
            }
        }

        throw new \RuntimeException(sprintf('Failed to write cache file "%s".', $file));
    }
}
