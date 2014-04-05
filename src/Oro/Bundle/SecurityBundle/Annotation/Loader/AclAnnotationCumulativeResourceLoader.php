<?php

namespace Oro\Bundle\SecurityBundle\Annotation\Loader;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceInfo;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader;

class AclAnnotationCumulativeResourceLoader implements CumulativeResourceLoader
{
    /**
     * @var string[]
     */
    private $subDirs;

    /**
     * Constructor
     *
     * @param string[] $subDirs  A list of sub directories (related to a bundle directory)
     *                           where classes with ACL annotations may be located
     */
    public function __construct(array $subDirs = [])
    {
        $this->subDirs = $subDirs;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return 'oro_acl_annotations';
    }

    /**
     * {@inheritdoc}
     */
    public function load($bundleClass, $bundleDir)
    {
        $finder = $this->getFileFinder($bundleDir);
        if (!$finder) {
            return null;
        }

        $files = [];
        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }
        if (empty($files)) {
            return null;
        }

        return new CumulativeResourceInfo(
            $bundleClass,
            $this->getResource(),
            null,
            $files
        );
    }

    /**
     * {@inheritdoc}
     */
    public function registerResource(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);

            $finder = $this->getFileFinder(dirname($reflection->getFileName()));
            if ($finder) {
                /** @var \SplFileInfo $file */
                foreach ($finder as $file) {
                    $container->addResource(new FileResource($file->getRealPath()));
                }
            }
        }
    }

    /**
     * @param $bundleDir
     * @return array
     */
    protected function getDirectoriesToCheck($bundleDir)
    {
        $result = [];

        if (!empty($this->subDirs)) {
            foreach ($this->subDirs as $subDir) {
                $dir = $bundleDir . DIRECTORY_SEPARATOR . $subDir;
                if (is_dir($dir)) {
                    $result[] = $dir;
                }
            }
        } else {
            $result[] = $bundleDir;
        }

        return $result;
    }

    /**
     * @param string $bundleDir
     * @return Finder|null
     */
    protected function getFileFinder($bundleDir)
    {
        $dirs = $this->getDirectoriesToCheck($bundleDir);
        if (empty($dirs)) {
            return null;
        }

        $finder = new Finder();
        $finder
            ->files()
            ->name('*.php')
            ->in($dirs)
            ->ignoreVCS(true);

        return $finder;
    }
}
