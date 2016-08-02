<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationCumulativeResourceLoader;
use Oro\Component\Config\Loader\CumulativeConfigLoader;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class AclAnnotationMetadataDumper
{
    const ANNOTATION_CACHE_CLASS = 'AclAnnotation';
    
    /** @var  array */
    protected $options;

    public function __construct(ParameterBag $parameterBag)
    {
        $this->options['cache_dir']   = $parameterBag->get('kernel.cache_dir');
        $this->options['environment'] = $parameterBag->get('kernel.environment');
        $this->options['debug']       = $parameterBag->get('kernel.debug');
        $this->options['name']        = $parameterBag->get('kernel.name');
    }

    /**
     * Write meta file with resources related to acl annotations
     */
    public function dump()
    {
        $aclContainer = new ContainerBuilder();
        self::getAclAnnotationLoader()->registerResources($aclContainer);

        $metaFile = $this->getMetaFile();
        $metadata = $aclContainer->getResources();

        $mode = 0666;
        $umask = umask();
        $filesystem = new Filesystem();

        if (null !== $metadata && true === $this->options['debug']) {
            $filesystem->dumpFile($metaFile, serialize($metadata), null);
            try {
                $filesystem->chmod($metaFile, $mode, $umask);
            } catch (IOException $e) {
                // discard chmod failure (some filesystem may not support it)
            }
        }
    }

    /**
     * @return string
     */
    public function getMetaFile()
    {
        $cacheDir    = $this->options['cache_dir'];
        $name        = $this->options['name'];
        $environment = $this->options['environment'];
        return $cacheDir.'/'.$name.ucfirst($environment).self::ANNOTATION_CACHE_CLASS.'.php.meta';
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
