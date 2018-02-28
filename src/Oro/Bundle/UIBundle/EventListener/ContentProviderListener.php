<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface;
use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ContentProviderListener
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
     * {@inheritdoc}
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            $request = $event->getRequest();
            $contentProvidersToEnable = $request->get('_enableContentProviders');
            $displayContentProviders = $request->get('_displayContentProviders');
            if ($contentProvidersToEnable || $displayContentProviders) {
                $contentProviderManager = $this->getContentProviderManager();
                if ($contentProvidersToEnable) {
                    foreach (explode(',', $contentProvidersToEnable) as $name) {
                        $contentProviderManager->enableContentProvider($name);
                    }
                }
                if ($displayContentProviders) {
                    $displayContentProviders = explode(',', $displayContentProviders);
                    /** @var ContentProviderInterface[] $providers */
                    $providers = $contentProviderManager->getContentProviders();
                    foreach ($providers as $provider) {
                        if (!in_array($provider->getName(), $displayContentProviders, true)) {
                            $provider->setEnabled(false);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return ContentProviderManager
     */
    protected function getContentProviderManager()
    {
        return $this->container->get('oro_ui.content_provider.manager');
    }
}
