<?php

namespace Oro\Bundle\SecurityBundle\Annotation;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Component\Config\CumulativeResource;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationMetadataDumper;

class AclListener
{
    /** @var AclAnnotationProvider */
    protected $cacheProvider;

    /** @var AclAnnotationMetadataDumper */
    protected $dumper;

    /** @var bool */
    protected $kernelDebug;

    /**
     * @param AclAnnotationProvider       $cacheProvider
     * @param AclAnnotationMetadataDumper $dumper
     * @param bool                        $kernelDebug
     */
    public function __construct(
        AclAnnotationProvider $cacheProvider,
        AclAnnotationMetadataDumper $dumper,
        $kernelDebug
    ) {
        $this->cacheProvider = $cacheProvider;
        $this->dumper = $dumper;
        $this->kernelDebug = $kernelDebug;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->isFresh()) {
            $this->cacheProvider->warmUpCache();
            $this->dumper->dump();
        }
    }

    /**
     * Checks if meta file for acl annotations is up-to-date
     *
     * @return bool
     */
    public function isFresh()
    {
        $file = $this->dumper->getMetaFile();
        if (!is_file($file)) {
            return false;
        }

        if (!$this->kernelDebug) {
            return true;
        }

        $time = filemtime($file);
        $meta = unserialize(file_get_contents($file));
        foreach ($meta as $resource) {
            /** @var CumulativeResource $resource */
            if (!$resource->isFresh($time)) {
                return false;
            }
        }

        return true;
    }
}
