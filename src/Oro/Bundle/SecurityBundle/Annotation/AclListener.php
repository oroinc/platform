<?php

namespace Oro\Bundle\SecurityBundle\Annotation;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationLoader;
use Oro\Bundle\SecurityBundle\DependencyInjection\OroSecurityExtension;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Metadata\ActionMetadataProvider;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;

class AclListener
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $dumper = $this->getConfigMetadataDumper();
        if (!$dumper->isFresh()) {
            $this->getAclAnnotationProvider()->warmUpCache();
            $this->getActionMetadataProvider()->warmUpCache();

            $tempAclContainer = new ContainerBuilder();
            $loader = AclAnnotationLoader::getAclAnnotationResourceLoader();
            $loader->registerResources($tempAclContainer);
            $loader = OroSecurityExtension::getAclConfigLoader();
            $loader->registerResources($tempAclContainer);

            $dumper->dump($tempAclContainer);
        }
    }

    /**
     * @return AclAnnotationProvider
     */
    protected function getAclAnnotationProvider()
    {
        return $this->container->get('oro_security.acl.annotation_provider');
    }

    /**
     * @return ActionMetadataProvider
     */
    protected function getActionMetadataProvider()
    {
        return $this->container->get('oro_security.action_metadata_provider');
    }

    /**
     * @return ConfigMetadataDumperInterface
     */
    protected function getConfigMetadataDumper()
    {
        return $this->container->get('oro_security.acl.annotation.metadata.dumper');
    }
}
