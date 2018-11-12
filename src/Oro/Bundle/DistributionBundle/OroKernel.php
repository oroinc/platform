<?php

namespace Oro\Bundle\DistributionBundle;

use Oro\Bundle\DistributionBundle\Dumper\PhpBundlesDumper;
use Oro\Bundle\DistributionBundle\Error\ErrorHandler;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use OroRequirements;
use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;
use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;
use Symfony\Component\ClassLoader\ClassCollectionLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

/**
 * This class should work on PHP 5.3
 * Keep old array syntax
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class OroKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    protected function initializeBundles()
    {
        // clear state of CumulativeResourceManager
        CumulativeResourceManager::getInstance()->clear();

        parent::initializeBundles();

        // initialize CumulativeResourceManager
        $bundles = array();
        foreach ($this->bundles as $name => $bundle) {
            $bundles[$name] = get_class($bundle);
        }
        CumulativeResourceManager::getInstance()
            ->setBundles($bundles)
            ->setAppRootDir($this->rootDir);
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = array();
        $cacheDir = $this->getCacheDir();
        if (!$cacheDir) {
            foreach ($this->collectBundles() as $class => $params) {
                $bundles[] = $params['kernel']
                    ? new $class($this)
                    : new $class;
            }
        } else {
            $cache = new ConfigCache($cacheDir . '/bundles.php', false);
            if (!$cache->isFresh()) {
                $dumper = new PhpBundlesDumper($this->collectBundles());
                $cache->write($dumper->dump());
            }

            // require instead of require_once used to correctly handle sub-requests
            $bundles = require $cache->getPath();
        }

        return $bundles;
    }

    /**
     * Finds all .../Resource/config/oro/bundles.yml in given root folders
     *
     * @param array $roots
     *
     * @return array
     */
    protected function findBundles($roots = array())
    {
        $paths = array();
        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }
            $root = realpath($root);
            $dir = new \RecursiveDirectoryIterator(
                $root,
                \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::SKIP_DOTS
            );
            $filter = new \RecursiveCallbackFilterIterator(
                $dir,
                function (\SplFileInfo $current) use (&$paths) {
                    if (!$current->getRealPath()) {
                        return false;
                    }
                    $fileName = strtolower($current->getFilename());
                    if ($fileName === 'tests' || $current->isFile()) {
                        return false;
                    }
                    if (!is_dir($current->getPathname() . '/Resources')) {
                        return true;
                    } else {
                        $file = $current->getPathname() . '/Resources/config/oro/bundles.yml';
                        if (is_file($file)) {
                            $paths[] = $file;
                        }

                        return false;
                    }
                }
            );

            $iterator = new \RecursiveIteratorIterator($filter);
            $iterator->rewind();
        }

        return $paths;
    }

    /**
     * @return array
     */
    protected function collectBundles()
    {
        $files = $this->findBundles(
            array(
                $this->getProjectDir() . '/src',
                $this->getProjectDir() . '/vendor'
            )
        );

        $bundles = array();
        $exclusions = array();
        foreach ($files as $file) {
            $import = Yaml::parse(file_get_contents($file));
            if (!empty($import)) {
                if (!empty($import['bundles'])) {
                    $bundles = array_merge($bundles, $this->getBundlesMapping($import['bundles']));
                }
                if (!empty($import['exclusions'])) {
                    $exclusions = array_merge($exclusions, $this->getBundlesMapping($import['exclusions']));
                }
            }
        }

        $bundles = array_diff_key($bundles, $exclusions);

        uasort($bundles, array($this, 'compareBundles'));

        return $bundles;
    }

    /**
     * @param $bundles
     *
     * @return array
     */
    protected function getBundlesMapping(array $bundles)
    {
        $result = array();
        foreach ($bundles as $bundle) {
            $kernel   = false;
            $priority = 0;

            if (is_array($bundle)) {
                $class    = $bundle['name'];
                $kernel   = isset($bundle['kernel']) && true == $bundle['kernel'];
                $priority = isset($bundle['priority']) ? (int)$bundle['priority'] : 0;
            } else {
                $class = $bundle;
            }

            $result[$class] = array(
                'name'     => $class,
                'kernel'   => $kernel,
                'priority' => $priority,
            );
        }

        return $result;
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    public function compareBundles($a, $b)
    {
        $p1 = (int)$a['priority'];
        $p2 = (int)$b['priority'];
        if ($p1 === $p2) {
            // bundles with the same priority are sorted alphabetically
            return strcasecmp((string)$a['name'], (string)$b['name']);
        }

        // sort be priority
        return ($p1 < $p2) ? -1 : 1;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function boot()
    {
        $phpVersion = phpversion();

        include_once $this->getProjectDir() . '/var/OroRequirements.php';

        if (!version_compare($phpVersion, OroRequirements::REQUIRED_PHP_VERSION, '>=')) {
            throw new \Exception(
                sprintf(
                    'PHP version must be at least %s (%s is installed)',
                    OroRequirements::REQUIRED_PHP_VERSION,
                    $phpVersion
                )
            );
        }

        parent::boot();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function dumpContainer(ConfigCache $cache, ContainerBuilder $container, $class, $baseClass)
    {
        // cache the container
        $dumper = new PhpDumper($container);

        if ($container->getParameter('installed')
            && class_exists('ProxyManager\Configuration')
            && class_exists('Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper')
        ) {
            $dumper->setProxyDumper(new ProxyDumper());
        }

        $content = $dumper->dump(array(
            'class' => $class,
            'base_class' => $baseClass,
            'file' => $cache->getPath(),
            'as_files' => true,
            'debug' => $this->debug,
            'inline_class_loader_parameter' => \PHP_VERSION_ID >= 70000 && !$this->loadClassCache
                && !class_exists(ClassCollectionLoader::class, false)
                ? 'container.dumper.inline_class_loader'
                : null,
            'build_time' => $container->hasParameter('kernel.container_build_time')
                ? $container->getParameter('kernel.container_build_time')
                : time(),
        ));

        $rootCode = array_pop($content);
        $dir = dirname($cache->getPath()).'/';
        $fs = new Filesystem();

        foreach ($content as $file => $code) {
            $fs->dumpFile($dir.$file, $code);
            @chmod($dir.$file, 0666 & ~umask());
        }
        @unlink(dirname($dir.$file).'.legacy');

        $cache->write($rootCode, $container->getResources());

        // we should not use parent::stripComments method to cleanup source code from the comments to avoid
        // memory leaks what generate token_get_all function.
        //@TODO investigate actuality memory leaks what generate token_get_all function in scope BAP-15236.
//        if (!$this->debug) {
//            $cache->write(php_strip_whitespace($cache->getPath()), $container->getResources());
//        }
    }

    /**
     * Add custom error handler
     */
    protected function initializeContainer()
    {
        if ($this->getEnvironment() !== 'test') {
            $handler = new ErrorHandler();
            $handler->registerHandlers();
        }

        parent::initializeContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function getBundle($name, $first = true)
    {
        // if need to get this precise bundle
        if (strpos($name, '!') === 0) {
            $name = substr($name, 1);
            if (isset($this->bundleMap[$name])) {
                // current bundle is always the last
                $bundle = end($this->bundleMap[$name]);
                return $first ? $bundle : array($bundle);
            }
        }

        return parent::getBundle($name, $first);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerBuilder()
    {
        $container = new ExtendedContainerBuilder(new ParameterBag($this->getKernelParameters()));

        if (class_exists('ProxyManager\Configuration')
            && class_exists('Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator')
        ) {
            $container->setProxyInstantiator(new RuntimeInstantiator());
        }

        return $container;
    }
}
