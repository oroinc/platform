<?php

namespace Oro\Bundle\SecurityBundle\Annotation;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Component\Config\Dumper\CumulativeConfigMetadataDumper;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationLoader;

class AclListener
{
    /** @var AclAnnotationProvider */
    protected $cacheProvider;

    /** @var CumulativeConfigMetadataDumper */
    protected $dumper;

    /**
     * @param AclAnnotationProvider $cacheProvider
     * @param ConfigMetadataDumperInterface $dumper
     */
    public function __construct(AclAnnotationProvider $cacheProvider, ConfigMetadataDumperInterface $dumper)
    {
        $this->cacheProvider = $cacheProvider;
        $this->dumper = $dumper;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->dumper->isFresh()) {
            $this->cacheProvider->warmUpCache();

            $tempAclContainer = new ContainerBuilder();
            $loader = AclAnnotationLoader::getAclAnnotationResourceLoader();
            $loader->registerResources($tempAclContainer);

            $this->dumper->dump($tempAclContainer);
        }
    }
}
