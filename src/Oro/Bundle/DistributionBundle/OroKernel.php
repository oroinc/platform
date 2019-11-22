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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

/**
 * Adds the following features:
 * * loading bundles based on "Resources/config/oro/bundles.yml" configuration files
 * * loading configuration from "Resources/config/oro/*.yml" configuration files
 * * extended error handling, {@see \Oro\Bundle\DistributionBundle\Error\ErrorHandler}
 */
abstract class OroKernel extends Kernel
{
    private $warmupDir;

    /**
     * {@inheritdoc}
     */
    protected function initializeBundles()
    {
        // clear state of CumulativeResourceManager
        CumulativeResourceManager::getInstance()->clear();

        parent::initializeBundles();

        // initialize CumulativeResourceManager
        $bundles = [];
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
        $bundles = [];
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
    protected function findBundles($roots = [])
    {
        $paths = [];
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
                    }

                    $file = $current->getPathname() . '/Resources/config/oro/bundles.yml';
                    if (is_file($file)) {
                        $paths[] = $file;
                    }

                    return false;
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
        $files = $this->findBundles([
            $this->getProjectDir() . '/src',
            $this->getProjectDir() . '/vendor'
        ]);

        $bundles = [];
        $exclusions = [];
        foreach ($files as $file) {
            $import = Yaml::parse(file_get_contents($file));
            if (!empty($import)) {
                if (!empty($import['bundles'])) {
                    $bundles[] = $this->getBundlesMapping($import['bundles']);
                }
                if (!empty($import['exclusions'])) {
                    $exclusions[] = $this->getBundlesMapping($import['exclusions']);
                }
            }
        }

        if ($bundles) {
            $bundles = array_merge(...$bundles);
        }
        if ($exclusions) {
            $exclusions = array_merge(...$exclusions);
            $bundles = array_diff_key($bundles, $exclusions);
        }

        uasort($bundles, [$this, 'compareBundles']);

        return $bundles;
    }

    /**
     * @param $bundles
     *
     * @return array
     */
    protected function getBundlesMapping(array $bundles)
    {
        $result = [];
        foreach ($bundles as $bundle) {
            $kernel = false;
            $priority = 0;

            if (\is_array($bundle)) {
                $class = $bundle['name'];
                $kernel = isset($bundle['kernel']) && $bundle['kernel'];
                $priority = isset($bundle['priority']) ? (int)$bundle['priority'] : 0;
            } else {
                $class = $bundle;
            }

            $result[$class] = [
                'name'     => $class,
                'kernel'   => $kernel,
                'priority' => $priority
            ];
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
            throw new \RuntimeException(sprintf(
                'PHP version must be at least %s (%s is installed)',
                OroRequirements::REQUIRED_PHP_VERSION,
                $phpVersion
            ));
        }

        parent::boot();
    }

    /**
     * {@inheritdoc}
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

        $content = $dumper->dump([
            'class' => $class,
            'base_class' => $baseClass,
            'file' => $cache->getPath(),
            'as_files' => true,
            'debug' => $this->debug,
            'inline_class_loader_parameter' => !class_exists(ClassCollectionLoader::class, false)
                ? 'container.dumper.inline_class_loader'
                : null,
            'build_time' => $container->hasParameter('kernel.container_build_time')
                ? $container->getParameter('kernel.container_build_time')
                : time(),
        ]);

        $rootCode = array_pop($content);
        $dir = \dirname($cache->getPath()).'/';
        $fs = new Filesystem();

        foreach ($content as $file => $code) {
            $fs->dumpFile($dir.$file, $code);
            @chmod($dir.$file, 0666 & ~umask());
        }
        $legacyFile = \dirname($dir.$file).'.legacy';
        if (file_exists($legacyFile)) {
            @unlink($legacyFile);
        }

        $cache->write($rootCode, $container->getResources());
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeContainer()
    {
        if ($this->getEnvironment() !== 'test') {
            $handler = new ErrorHandler();
            $handler->registerHandlers();
        }

        $class = $this->getContainerClass();
        $cacheDir = $this->warmupDir ?: $this->getCacheDir();
        $cache = new ConfigCache($cacheDir.'/'.$class.'.php', $this->debug);
        $oldContainer = null;
        if ($fresh = $cache->isFresh()) {
            // Silence E_WARNING to ignore "include" failures - don't use "@" to prevent silencing fatal errors
            $errorLevel = error_reporting(\E_ALL ^ \E_WARNING);
            $fresh = $oldContainer = false;
            try {
                if (file_exists($cache->getPath()) && \is_object($this->container = include $cache->getPath())) {
                    $this->container->set('kernel', $this);
                    $oldContainer = $this->container;
                    $fresh = true;
                }
            } catch (\Throwable $e) {
            } catch (\Exception $e) {
            } finally {
                error_reporting($errorLevel);
            }
        }

        if ($fresh) {
            return;
        }

        if ($this->debug) {
            $collectedLogs = array();
            /** @var callable|string $previousHandler */
            $previousHandler = defined('PHPUNIT_COMPOSER_INSTALL');
            $previousHandler = $previousHandler ?: set_error_handler(function ($type, $message, $file, $line) use (&$collectedLogs, &$previousHandler) {
                if (E_USER_DEPRECATED !== $type && E_DEPRECATED !== $type) {
                    return $previousHandler ? $previousHandler($type, $message, $file, $line) : false;
                }

                if (isset($collectedLogs[$message])) {
                    ++$collectedLogs[$message]['count'];

                    return;
                }

                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
                // Clean the trace by removing first frames added by the error handler itself.
                for ($i = 0; isset($backtrace[$i]); ++$i) {
                    if (isset($backtrace[$i]['file'], $backtrace[$i]['line']) && $backtrace[$i]['line'] === $line && $backtrace[$i]['file'] === $file) {
                        $backtrace = array_slice($backtrace, 1 + $i);
                        break;
                    }
                }

                $collectedLogs[$message] = array(
                    'type' => $type,
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                    'trace' => $backtrace,
                    'count' => 1,
                );
            });
        }

        try {
            $container = null;
            $container = $this->buildContainer();

            if ($cache->isFresh()) {
                $errorLevel = error_reporting(\E_ALL ^ \E_WARNING);
                try {
                    if (file_exists($cache->getPath()) && \is_object($this->container = include $cache->getPath())) {
                        $this->container->set('kernel', $this);
                        $fresh = true;
                    }
                } catch (\Throwable $e) {
                } catch (\Exception $e) {
                } finally {
                    error_reporting($errorLevel);
                }
            }
            if (!$fresh) {
                $container->compile();
            }
        } finally {
            if ($this->debug && true !== $previousHandler) {
                restore_error_handler();
                if (!$fresh) {
                    file_put_contents($cacheDir.'/'.$class.'Deprecations.log', serialize(array_values($collectedLogs)));
                    file_put_contents($cacheDir.'/'.$class.'Compiler.log', null !== $container ? implode("\n", $container->getCompiler()->getLog()) : '');
                }
            }
        }

        if ($fresh) {
            if ($this->container->has('cache_warmer')) {
                $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir'));
            }
            return;
        }

        if (null === $oldContainer && file_exists($cache->getPath())) {
            $errorLevel = error_reporting(\E_ALL ^ \E_WARNING);
            try {
                $oldContainer = include $cache->getPath();
            } catch (\Throwable $e) {
            } catch (\Exception $e) {
            } finally {
                error_reporting($errorLevel);
            }
        }
        $oldContainer = is_object($oldContainer) ? new \ReflectionClass($oldContainer) : false;

        $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass());
        $this->container = require $cache->getPath();
        $this->container->set('kernel', $this);

        if ($oldContainer && get_class($this->container) !== $oldContainer->name) {
            // Because concurrent requests might still be using them,
            // old container files are not removed immediately,
            // but on a next dump of the container.
            static $legacyContainers = array();
            $oldContainerDir = dirname($oldContainer->getFileName());
            $legacyContainers[$oldContainerDir.'.legacy'] = true;
            foreach (glob(dirname($oldContainerDir).DIRECTORY_SEPARATOR.'*.legacy') as $legacyContainer) {
                if (!isset($legacyContainers[$legacyContainer]) && @unlink($legacyContainer)) {
                    (new Filesystem())->remove(substr($legacyContainer, 0, -7));
                }
            }

            touch($oldContainerDir.'.legacy');
        }

        if ($this->container->has('cache_warmer')) {
            $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reboot($warmupDir)
    {
        $this->warmupDir = $warmupDir;
        parent::reboot($warmupDir);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerBuilder()
    {
        $container = new ExtendedContainerBuilder();
        $container->getParameterBag()->add($this->getKernelParameters());

        if ($this instanceof CompilerPassInterface) {
            $container->addCompilerPass($this, PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);
        }
        if (class_exists('ProxyManager\Configuration')
            && class_exists('Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator')
        ) {
            $container->setProxyInstantiator(new RuntimeInstantiator());
        }

        return $container;
    }
}
