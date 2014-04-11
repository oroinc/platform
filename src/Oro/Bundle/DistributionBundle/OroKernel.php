<?php

namespace Oro\Bundle\DistributionBundle;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Bundle\DistributionBundle\Dumper\PhpBundlesDumper;

abstract class OroKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    protected function initializeBundles()
    {
        parent::initializeBundles();

        // pass bundles to CumulativeResourceManager
        $bundles       = [];
        foreach ($this->bundles as $name => $bundle) {
            $bundles[$name] = get_class($bundle);
        }
        CumulativeResourceManager::getInstance()->setBundles($bundles);
    }

    /**
     * Get the list of all "autoregistered" bundles
     *
     * @return array List ob bundle objects
     */
    public function registerBundles()
    {
        // clear state of CumulativeResourceManager
        CumulativeResourceManager::getInstance()->clear();

        $bundles = array();

        if (!$this->getCacheDir()) {
            foreach ($this->collectBundles() as $class => $params) {
                $bundles[] = $params['kernel']
                    ? new $class($this)
                    : new $class;
            }
        } else {
            $file = $this->getCacheDir() . '/bundles.php';
            $cache = new ConfigCache($file, false);

            if (!$cache->isFresh($file)) {
                $dumper = new PhpBundlesDumper($this->collectBundles());

                $cache->write($dumper->dump());
            }

            // require instead of require_once used to correctly handle sub-requests
            $bundles = require $cache;
        }

        return $bundles;
    }

    /**
     * Finds all .../Resource/config/oro/bundles.yml in given root folders
     *
     * @param array $roots
     * @return array
     */
    protected function findBundles($roots = [])
    {
        $paths = [];
        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }
            $root   = realpath($root);
            $dir    = new \RecursiveDirectoryIterator($root, \FilesystemIterator::FOLLOW_SYMLINKS);
            $filter = new \RecursiveCallbackFilterIterator(
                $dir,
                function (\SplFileInfo $current) use (&$paths) {
                    $fileName = strtolower($current->getFilename());
                    if ($fileName === '.'
                        || $fileName === '..'
                        || $fileName === 'tests'
                        || $current->isFile()
                    ) {
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

    protected function collectBundles()
    {
        $files = $this->findBundles(
            [
                $this->getRootDir() . '/../src',
                $this->getRootDir() . '/../vendor'
            ]
        );
        foreach ($files as $file) {
            $import = Yaml::parse($file);

            foreach ($import['bundles'] as $bundle) {
                $kernel = false;
                $priority = 0;

                if (is_array($bundle)) {
                    $class = $bundle['name'];
                    $kernel = isset($bundle['kernel']) && true == $bundle['kernel'];
                    $priority = isset($bundle['priority']) ? (int)$bundle['priority'] : 0;
                } else {
                    $class = $bundle;
                }

                if (!isset($bundles[$class])) {
                    $bundles[$class] = array(
                        'name' => $class,
                        'kernel' => $kernel,
                        'priority' => $priority,
                    );
                }
            }
        }

        uasort($bundles, array($this, 'compareBundles'));

        return $bundles;
    }

    public function compareBundles($a, $b)
    {
        // @todo: this is preliminary algorithm. we need to implement more sophisticated one,
        // for example using bundle dependency info from composer.json
        $p1 = (int)$a['priority'];
        $p2 = (int)$b['priority'];

        if ($p1 == $p2) {
            $n1 = (string)$a['name'];
            $n2 = (string)$b['name'];

            // make sure OroCRM bundles follow Oro bundles
            if (strpos($n1, 'Oro') === 0 && strpos($n2, 'Oro') === 0) {
                if ((strpos($n1, 'OroCRM') === 0) && (strpos($n2, 'OroCRM') === 0)) {
                    return strcasecmp($n1, $n2);
                }
                if (strpos($n1, 'OroCRM') === 0) {
                    return 1;
                }
                if (strpos($n2, 'OroCRM') === 0) {
                    return -1;
                }
            }

            // bundles with the same priorities are sorted alphabetically
            return strcasecmp($n1, $n2);
        }

        // sort be priority
        return ($p1 < $p2) ? -1 : 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function dumpContainer(ConfigCache $cache, ContainerBuilder $container, $class, $baseClass)
    {
        // cache the container
        $dumper = new PhpDumper($container);

        if (class_exists('ProxyManager\Configuration')) {
            $dumper->setProxyDumper(new ProxyDumper());
        }

        $content = $dumper->dump(array('class' => $class, 'base_class' => $baseClass));
        $cache->write($content, $container->getResources());

        if (!$this->debug) {
            $cache->write(php_strip_whitespace($cache), $container->getResources());
        }
    }
}
