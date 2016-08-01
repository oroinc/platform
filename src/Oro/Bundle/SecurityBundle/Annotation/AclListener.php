<?php

namespace Oro\Bundle\SecurityBundle\Annotation;

use Oro\Bundle\SecurityBundle\Cache\AclAnnotationCacheWarmer;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationMetadataDumper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class AclListener
{
    /** @var ContainerInterface */
    private $container;

    /** @var AclAnnotationCacheWarmer  */
    private $cacheWarmer;

    /**
     * @param ContainerInterface $container
     * @param AclAnnotationCacheWarmer $cacheWarmer
     */
    public function __construct(
        ContainerInterface $container,
        AclAnnotationCacheWarmer $cacheWarmer
    ) {
        $this->container = $container;
        $this->cacheWarmer = $cacheWarmer;
        $this->cacheDir = $this->container->getParameter('kernel.cache_dir');
        $this->dumper = new AclAnnotationMetadataDumper($this->container->getParameterBag());
        $this->file = $this->dumper->getMetaFile();
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->isFresh()) {
            $this->cacheWarmer->warmUp($this->cacheDir.'/annotations');
            $this->dumper->dump();
        }
    }

    /**
     * Check is meta file for acl annotations fresh
     * @return bool
     */
    public function isFresh()
    {
        if (!is_file($this->file)) {
            return false;
        }
        if (!$this->container->getParameter('kernel.debug')) {
            return true;
        }
        $time = filemtime($this->file);
        $meta = unserialize(file_get_contents($this->file));
        foreach ($meta as $resource) {
            if (!$resource->isFresh($time)) {
                return false;
            }
        }
        return true;
    }
}
