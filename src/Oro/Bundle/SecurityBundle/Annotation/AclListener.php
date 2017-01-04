<?php

namespace Oro\Bundle\SecurityBundle\Annotation;

use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationLoader;
use Oro\Bundle\SecurityBundle\DependencyInjection\OroSecurityExtension;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Metadata\ActionMetadataProvider;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Component\Config\Dumper\CumulativeConfigMetadataDumper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class AclListener
{
    /** @var AclAnnotationProvider */
    protected $cacheProvider;

    /** @var CumulativeConfigMetadataDumper */
    protected $dumper;

    /**
     * @param AclAnnotationProvider $cacheProvider
     * @param ActionMetadataProvider $actionProvider
     * @param ConfigMetadataDumperInterface $dumper
     */
    public function __construct(
        AclAnnotationProvider $cacheProvider,
        ActionMetadataProvider $actionProvider,
        ConfigMetadataDumperInterface $dumper
    ) {
        $this->cacheProvider = $cacheProvider;
        $this->actionMetadataProvider = $actionProvider;
        $this->dumper = $dumper;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->dumper->isFresh()) {
            $this->cacheProvider->warmUpCache();
            $this->actionMetadataProvider->warmUpCache();

            $tempAclContainer = new ContainerBuilder();
            $loader = AclAnnotationLoader::getAclAnnotationResourceLoader();
            $loader->registerResources($tempAclContainer);
            $loader = OroSecurityExtension::getAclConfigLoader();
            $loader->registerResources($tempAclContainer);

            $this->dumper->dump($tempAclContainer);
        }
    }
}
