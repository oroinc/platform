<?php

namespace Oro\Bundle\DistributionBundle;

use Oro\Bundle\DistributionBundle\Dumper\PhpBundlesDumper;
use Oro\Bundle\DistributionBundle\Error\ErrorHandler;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendReflectionErrorHandler;
use Oro\Bundle\PlatformBundle\Profiler\ProfilerConfig;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;
use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;
use Symfony\Component\ClassLoader\ClassCollectionLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\ErrorHandler\DebugClassLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

/**
 * Adds the following features:
 * * loading bundles based on "Resources/config/oro/bundles.yml" configuration files
 * * loading configuration from "Resources/config/oro/*.yml" configuration files
 * * extended error handling, {@see \Oro\Bundle\DistributionBundle\Error\ErrorHandler}
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class OroKernel extends Kernel
{
    public const REQUIRED_PHP_VERSION = '8.2';

    /** @var string|null */
    private $warmupDir;

    /** @var array */
    private static $freshCache = [];

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        if ($debug) {
            ExtendReflectionErrorHandler::initialize();
        }
    }

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
        if (class_exists('AppKernel')) {
            $bundles['app.kernel'] = \AppKernel::class;
        }
        CumulativeResourceManager::getInstance()
            ->setBundles($bundles)
            ->setAppRootDir($this->getProjectDir());
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $cacheDir = $this->warmupDir ?: $this->getCacheDir();
        $cache = new ConfigCache($cacheDir . '/bundles.php', false);
        if (!$cache->isFresh()) {
            $dumper = new PhpBundlesDumper($this->collectBundles());
            $cache->write($dumper->dump());
        }

        // require instead of require_once used to correctly handle sub-requests
        return require $cache->getPath();
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
     * @param string $junction
     *
     * @return bool
     */
    protected function isJunction(string $junction)
    {
        if (!\defined('PHP_WINDOWS_VERSION_BUILD')) {
            return false;
        }

        // Important to clear all caches first
        clearstatcache(true, $junction);
        if (!is_dir($junction) || is_link($junction)) {
            return false;
        }
        $stat = lstat($junction);

        // S_ISDIR test (S_IFDIR is 0x4000, S_IFMT is 0xF000 bitmask)
        return $stat ? 0x4000 !== ($stat['mode'] & 0xf000) : false;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return array
     */
    protected function collectBundles()
    {
        $roots = [
            $this->getProjectDir() . '/src',
            $this->getProjectDir() . '/vendor',
        ];
        $files = [];

        // Resolves issues of '\RecursiveDirectoryIterator' with monolithic repository
        // since its content is copied as junctions under Windows.
        if (\defined('PHP_WINDOWS_VERSION_BUILD') && is_dir($this->getProjectDir() . '/vendor/oro')) {
            $directory = new \RecursiveDirectoryIterator(
                $this->getProjectDir() . '/vendor/oro',
                \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
            );
            $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
            $iterator->setMaxDepth(1);
            /** @var \SplFileInfo $info */
            foreach ($iterator as $info) {
                if ($info->isFile() || !$this->isJunction($info->getPathname())) {
                    continue;
                }

                // Resolves bundles.yml files on first level of junction so these folders do not participate
                // in find bundles routine once again.
                if (file_exists($info->getPathname() . '/Resources/config/oro/bundles.yml')) {
                    $files[] = $info->getPathname() . '/Resources/config/oro/bundles.yml';
                } else {
                    $roots[] = $info->getPathname();
                }
            }
        }

        $files = array_merge($this->findBundles($roots), $files);

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
            $optional = false;

            if (\is_array($bundle)) {
                $class = $bundle['name'];
                $kernel = $bundle['kernel'] ?? false;
                $priority = (int)($bundle['priority'] ?? 0);
                $optional = $bundle['optional'] ?? false;
            } else {
                $class = $bundle;
            }

            if (!$optional || class_exists($class)) {
                $result[$class] = [
                    'name'     => $class,
                    'kernel'   => $kernel,
                    'priority' => $priority
                ];
            }
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
        $p1 = (int)($a['priority'] ?? 0);
        $p2 = (int)($b['priority'] ?? 0);
        if ($p1 === $p2) {
            // bundles with the same priority are sorted alphabetically
            return strcasecmp((string)$a['name'], (string)$b['name']);
        }

        // sort be priority
        return ($p1 < $p2) ? -1 : 1;
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $phpVersion = phpversion();
        if (!version_compare($phpVersion, self::REQUIRED_PHP_VERSION, '>=')) {
            throw new \RuntimeException(sprintf(
                'PHP version must be at least %s (%s is installed)',
                self::REQUIRED_PHP_VERSION,
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

        $dumper->setProxyDumper(new ProxyDumper());

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
     * Add custom error handler
     * Disable container file lock for correct dump extended entities in the background process
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function initializeContainer()
    {
        if ($this->getEnvironment() !== 'test') {
            ErrorHandler::register();
        }

        $class = $this->getContainerClass();
        $cacheDir = $this->warmupDir ?: $this->getCacheDir();
        $debug = $this->debug && ProfilerConfig::trackContainerChanges();
        $cache = new ConfigCache($cacheDir.'/'.$class.'.php', $debug);
        $cachePath = $cache->getPath();

        // Silence E_WARNING to ignore "include" failures - don't use "@" to prevent silencing fatal errors
        $errorLevel = error_reporting(\E_ALL ^ \E_WARNING);
        // @codingStandardsIgnoreStart
        try {
            if (file_exists($cachePath) && \is_object($this->container = include $cachePath)
                && (!$this->debug || (self::$freshCache[$k = $cachePath.'.'.$this->environment] ?? self::$freshCache[$k] = $cache->isFresh()))
            ) {
                $this->container->set('kernel', $this);
                error_reporting($errorLevel);

                return;
            }
        } catch (\Throwable $e) {
        }

        $oldContainer = \is_object($this->container) ? new \ReflectionClass($this->container) : $this->container = null;

        try {
            is_dir($cacheDir) ?: mkdir($cacheDir, 0777, true);

            if ($lock = fopen($cachePath, 'w')) {
                chmod($cachePath, 0666 & ~umask());
                //flock($lock, LOCK_EX | LOCK_NB, $wouldBlock);
                //
                //if (!flock($lock, $wouldBlock ? LOCK_SH : LOCK_EX)) {
                //    fclose($lock);
                //} else {
                $cache = new class($cachePath, $this->debug) extends ConfigCache {
                    public $lock;

                    public function write($content, array $metadata = null)
                    {
                        rewind($this->lock);
                        ftruncate($this->lock, 0);
                        fwrite($this->lock, $content);

                        if (null !== $metadata) {
                            file_put_contents($this->getPath().'.meta', serialize($metadata));
                            @chmod($this->getPath().'.meta', 0666 & ~umask());
                        }

                        if (\function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
                            opcache_invalidate($this->getPath(), true);
                        }
                    }

                    public function __destruct()
                    {
                        flock($this->lock, LOCK_UN);
                        fclose($this->lock);
                    }
                };
                $cache->lock = $lock;

                if (!\is_object($this->container = include $cachePath)) {
                    $this->container = null;
                } elseif (!$oldContainer || \get_class($this->container) !== $oldContainer->name) {
                    $this->container->set('kernel', $this);

                    return;
                }
                //}
            }
        } catch (\Throwable $e) {
        } finally {
            error_reporting($errorLevel);
        }

        if ($collectDeprecations = $this->debug && !\defined('PHPUNIT_COMPOSER_INSTALL')) {
            $collectedLogs = [];
            $previousHandler = set_error_handler(function ($type, $message, $file, $line) use (&$collectedLogs, &$previousHandler) {
                if (E_USER_DEPRECATED !== $type && E_DEPRECATED !== $type) {
                    return $previousHandler ? $previousHandler($type, $message, $file, $line) : false;
                }

                if (isset($collectedLogs[$message])) {
                    ++$collectedLogs[$message]['count'];

                    return null;
                }

                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
                // Clean the trace by removing first frames added by the error handler itself.
                for ($i = 0; isset($backtrace[$i]); ++$i) {
                    if (isset($backtrace[$i]['file'], $backtrace[$i]['line']) && $backtrace[$i]['line'] === $line && $backtrace[$i]['file'] === $file) {
                        $backtrace = \array_slice($backtrace, 1 + $i);
                        break;
                    }
                }
                // Remove frames added by DebugClassLoader.
                for ($i = \count($backtrace) - 2; 0 < $i; --$i) {
                    if (($backtrace[$i]['class'] ?? '') === DebugClassLoader::class) {
                        $backtrace = [$backtrace[$i + 1]];
                        break;
                    }
                }

                $collectedLogs[$message] = [
                    'type' => $type,
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                    'trace' => [$backtrace[0]],
                    'count' => 1,
                ];

                return null;
            });
        }

        try {
            $container = null;
            $container = $this->buildContainer();
            $container->compile();
        } finally {
            if ($collectDeprecations && $container?->getParameter('oro_platform.collect_deprecations')) {
                restore_error_handler();

                file_put_contents($cacheDir.'/'.$class.'Deprecations.log', serialize(array_values($collectedLogs)));
                file_put_contents($cacheDir.'/'.$class.'Compiler.log', null !== $container ? implode("\n", $container->getCompiler()->getLog()) : '');
            }
        }

        $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass());
        unset($cache);
        $this->container = require $cachePath;
        $this->container->set('kernel', $this);

        if ($oldContainer && \get_class($this->container) !== $oldContainer->name) {
            // Because concurrent requests might still be using them,
            // old container files are not removed immediately,
            // but on a next dump of the container.
            static $legacyContainers = [];
            $oldContainerDir = \dirname($oldContainer->getFileName());
            $legacyContainers[$oldContainerDir.'.legacy'] = true;
            foreach (glob(\dirname($oldContainerDir).\DIRECTORY_SEPARATOR.'*.legacy', GLOB_NOSORT) as $legacyContainer) {
                if (!isset($legacyContainers[$legacyContainer]) && @unlink($legacyContainer)) {
                    (new Filesystem())->remove(substr($legacyContainer, 0, -7));
                }
            }

            touch($oldContainerDir.'.legacy');
        }
        // @codingStandardsIgnoreEnd
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
    protected function getContainerBuilder(): ContainerBuilder
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
