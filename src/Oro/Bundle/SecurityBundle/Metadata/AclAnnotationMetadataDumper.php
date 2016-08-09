<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationCumulativeResourceLoader;

class AclAnnotationMetadataDumper
{
    const ANNOTATION_CACHE_CLASS = 'AclAnnotation';

    /** @var string */
    protected $kernelCacheDir;

    /** @var string */
    protected $kernelEnvironment;

    /** @var string */
    protected $kernelName;

    /**
     * @param string $kernelCacheDir
     * @param string $kernelEnvironment
     * @param string $kernelName
     */
    public function __construct($kernelCacheDir, $kernelEnvironment, $kernelName)
    {
        $this->kernelCacheDir = $kernelCacheDir;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->kernelName = $kernelName;
    }

    /**
     * Write meta file with resources related to acl annotations
     */
    public function dump()
    {
        $container = new ContainerBuilder();
        self::getAclAnnotationLoader()->registerResources($container);

        $metadata = $container->getResources();
        $mode = 0666;
        $umask = umask();
        $filesystem = new Filesystem();

        $metaFile = $this->getMetaFile();
        $filesystem->dumpFile($metaFile, serialize($metadata), null);
        try {
            $filesystem->chmod($metaFile, $mode, $umask);
        } catch (IOException $e) {
            // discard chmod failure (some filesystem may not support it)
        }
    }

    /**
     * @return string
     */
    public function getMetaFile()
    {
        return
            $this->kernelCacheDir . '/' . $this->kernelName
            . ucfirst($this->kernelEnvironment) . self::ANNOTATION_CACHE_CLASS . '.php.meta';
    }

    /**
     * @return CumulativeConfigLoader
     */
    public static function getAclAnnotationLoader()
    {
        return new CumulativeConfigLoader(
            'oro_acl_annotation',
            new AclAnnotationCumulativeResourceLoader(['Controller'])
        );
    }
}
