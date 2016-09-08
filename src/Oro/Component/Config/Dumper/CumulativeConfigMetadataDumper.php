<?php
namespace Oro\Component\Config\Dumper;

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CumulativeConfigMetadataDumper implements ConfigMetadataDumperInterface
{
    /** @var string */
    protected $cacheDir;

    /** @var bool */
    protected $isDebug;

    /** @var  array */
    protected $options;

    /** @var  string */
    protected $passName;

    /**
     * CumulativeConfigMetadataDumper constructor.
     *
     * @param string $cacheDirectory
     * @param bool   $kernelDebug
     * @param string $cacheName
     */
    public function __construct($cacheDirectory, $kernelDebug, $cacheName)
    {
        $this->isDebug = $kernelDebug;
        $this->cacheDir = $cacheDirectory;
        $this->passName = $cacheName;
    }

    /**
     * Write meta file with resources related to acl annotations
     * @param ContainerBuilder $container
     */
    public function dump(ContainerBuilder $container)
    {
        $metaFile = $this->getMetaFile();
        $metadata = $container->getResources();
        $mode = 0666;
        $umask = umask();
        $filesystem = new Filesystem();
        if (null !== $metadata && true === $this->isDebug) {
            $filesystem->dumpFile($metaFile, serialize($metadata), null);
            try {
                $filesystem->chmod($metaFile, $mode, $umask);
            } catch (IOException $e) {
                // discard chmod failure (some filesystem may not support it)
            }
        }
    }

    /**
     * Check are config resources fresh?
     * @return bool
     */
    public function isFresh()
    {
        if (!$this->isDebug) {
            return true;
        }

        $file = $this->getMetaFile();
        if (!is_file($file)) {
            return false;
        }

        $time = filemtime($file);
        $meta = unserialize(file_get_contents($file));
        /** @var ResourceInterface $resource */
        foreach ($meta as $resource) {
            if (!$resource->isFresh($time)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string
     */
    protected function getMetaFile()
    {
        return sprintf('%s/oro/oro_config_meta/%s.meta', $this->cacheDir, $this->passName);
    }
}
