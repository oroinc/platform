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

    /**
     * {@inheritdoc}
     */
    protected function buildContainer()
    {
        $container = parent::buildContainer();

        $parametersConfig = $this->getProjectDir() . '/config/parameters.yml';
        if (!file_exists($parametersConfig)) {
            return $container;
        }

        $parameters = Yaml::parse(file_get_contents($parametersConfig)) ?: [];

        $deploymentType = $parameters['parameters']['deployment_type'] ?? '';
        if (!$deploymentType) {
            return $container;
        }

        $deploymentConfig = sprintf('%s/config/deployment/config_%s.yml', $this->getProjectDir(), $deploymentType);
        if (!file_exists($deploymentConfig)) {
            throw new \LogicException(
                sprintf('Deployment config "%s" for type "%s" not found.', $deploymentConfig, $deploymentType)
            );
        }
        $this->getContainerLoader($container)->load($deploymentConfig);

        return $container;
    }
}
