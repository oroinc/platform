<?php

namespace Oro\Component\Layout\Loader\Driver;

use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;
use Oro\Component\Layout\Loader\Visitor\ElementDependentVisitor;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

/**
 * Abstract implementation of DriverInterface based on a filesystem cache storage,
 * to load layout update instance  based on given file resource.
 */
abstract class AbstractDriver implements DriverInterface
{
    const CLASS_PREFIX = '__Oro_Layout_Update_';

    const ELEMENT_PREFIX = '_';

    /** @var LayoutUpdateGeneratorInterface */
    private $generator;

    /** @var bool */
    private $debug;

    /** @var string */
    private $cacheDir;

    /**
     * @param LayoutUpdateGeneratorInterface $generator
     * @param bool                           $debug
     * @param string                         $cacheDir
     */
    public function __construct(LayoutUpdateGeneratorInterface $generator, $debug, $cacheDir)
    {
        if (empty($cacheDir)) {
            throw new \InvalidArgumentException('Cache directory must not be empty.');
        }

        $this->generator = $generator;
        $this->debug     = $debug;
        $this->cacheDir  = $cacheDir;
    }

    /**
     * {@inheritdoc}
     */
    public function load($file)
    {
        $className = $this->generateClassName($file);

        if (!class_exists($className, false)) {
            $cacheFilename = $this->getCacheFilename($file);
            // write cache to file, refresh cache if source file fresher then cached one in debug mode
            if (!is_file($cacheFilename) || ($this->isDebug() && filemtime($file) > filemtime($cacheFilename))) {
                $this->writeCacheFile($cacheFilename, $this->doGenerate($className, $file));
            }

            require_once $cacheFilename;
        }

        return new $className($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateFilenamePattern($fileExtension)
    {
        return '/\.' . $fileExtension . '$/';
    }

    /**
     * @param string $className
     * @param string $file
     *
     * @return string
     */
    protected function doGenerate($className, $file)
    {
        $resourceDataForGenerator = $this->loadResourceGeneratorData($file);

        try {
            $visitors = $this->prepareVisitors($file);

            return $this->getGenerator()->generate($className, $resourceDataForGenerator, $visitors);
        } catch (SyntaxException $e) {
            $message = $e->getMessage() . "\n" . $this->dumpSource($e->getSource());
            $message .= str_repeat("\n", 2) . 'Filename: ' . $file;

            throw new \RuntimeException($message, 0, $e);
        }
    }

    /**
     * Loads file resource content and prepares generator data.
     *
     * @param string $file
     *
     * @return GeneratorData
     */
    abstract protected function loadResourceGeneratorData($file);

    /**
     * @param string $file
     *
     * @return null|VisitorCollection
     */
    protected function prepareVisitors($file)
    {
        $name = pathinfo($file, PATHINFO_FILENAME);

        if (str_starts_with($name, self::ELEMENT_PREFIX)) {
            return new VisitorCollection([new ElementDependentVisitor(substr($name, 1))]);
        }

        return null;
    }

    /**
     * @return LayoutUpdateGeneratorInterface
     */
    protected function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @return boolean
     */
    protected function isDebug()
    {
        return $this->debug;
    }

    /**
     * Gets the cache directory
     *
     * @return string
     */
    protected function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Dumps source back to human readable representation for error reporting. Could be overridden in descendants.
     *
     * @param mixed $source
     *
     * @return mixed
     */
    protected function dumpSource($source)
    {
        return $source;
    }

    /**
     * Generates PHP class name based on given resource name
     *
     * @param string $name
     *
     * @return string
     */
    protected function generateClassName($name)
    {
        return static::CLASS_PREFIX . hash('sha256', $this->normalizeName($name));
    }

    /**
     * Generates PHP filename based on given resource name
     *
     * @param string $name Resource filename
     *
     * @return bool|string Returns FALSE if cache dir isn't configured or generated PHP absolute filename otherwise
     */
    protected function getCacheFilename($name)
    {
        $class = substr($this->generateClassName($name), strlen(static::CLASS_PREFIX));

        return str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            sprintf(
                '%s/%s/%s.php',
                $this->getCacheDir(),
                substr($class, 0, 4),
                substr($class, 4)
            )
        );
    }

    /**
     * Write content to file, creates directories recursively. Throws excretion if cache directory is not writable
     *
     * @param string $file
     * @param string $content
     */
    protected function writeCacheFile($file, $content)
    {
        $dir = dirname($file);
        $this->createCacheDirectory($dir);

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

    protected function createCacheDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true)) {
                $error = error_get_last();
                clearstatcache(true, $dir);
                if (!\is_dir($dir)) {
                    // Try to call is_dir again in 0.01 second, in case the parallel process already created it
                    usleep(10000);
                    if (!\is_dir($dir)) {
                        throw new \RuntimeException(
                            sprintf('Unable to create the cache directory (%s). %s', $dir, $error['message'])
                        );
                    }
                }
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(sprintf('Unable to write in the cache directory (%s).', $dir));
        }
    }

    /**
     * Remove special characters from filename.
     *
     * @param string $name
     *
     * @return string
     */
    protected function normalizeName($name)
    {
        return preg_replace('#/{2,}#', '/', strtr((string)$name, '\\', '/'));
    }
}
