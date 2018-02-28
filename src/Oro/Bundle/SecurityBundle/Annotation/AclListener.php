<?php

namespace Oro\Bundle\SecurityBundle\Annotation;

use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationLoader;
use Oro\Bundle\SecurityBundle\DependencyInjection\OroSecurityExtension;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Metadata\ActionMetadataProvider;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class AclListener
{
    /** @var ConfigMetadataDumperInterface */
    private $dumper;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ConfigMetadataDumperInterface $dumper
     * @param ContainerInterface            $container
     */
    public function __construct(ConfigMetadataDumperInterface $dumper, ContainerInterface $container)
    {
        $this->dumper = $dumper;
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest() && !$this->dumper->isFresh()) {
            $this->getAclAnnotationProvider()->warmUpCache();
            $this->getActionMetadataProvider()->warmUpCache();

            $container = new ContainerBuilder();
            $loader = AclAnnotationLoader::getAclAnnotationResourceLoader();
            $loader->registerResources($container);
            $loader = OroSecurityExtension::getAclConfigLoader();
            $loader->registerResources($container);
            $this->dumper->dump($container);
        }
    }

    /**
     * @return AclAnnotationProvider
     */
    private function getAclAnnotationProvider()
    {
        return $this->container->get('oro_security.acl.annotation_provider');
    }

    /**
     * @return ActionMetadataProvider
     */
    private function getActionMetadataProvider()
    {
        return $this->container->get('oro_security.action_metadata_provider');
    }
}
