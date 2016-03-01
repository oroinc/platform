<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface;
use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;

class ContentProviderListener
{
    /**
     * @var ContentProviderManager
     */
    private $contentProviderManager = false;

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
                if ($contentProvidersToEnable) {
                    foreach (explode(',', $contentProvidersToEnable) as $name) {
                        $this->getContentProviderManager()->enableContentProvider($name);
                    }
                }

                if ($displayContentProviders) {
                    $displayContentProviders = explode(',', $displayContentProviders);
                    /** @var ContentProviderInterface $provider */
                    foreach ($this->getContentProviderManager()->getContentProviders() as $provider) {
                        if (!in_array($provider->getName(), $displayContentProviders)) {
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
        if ($this->contentProviderManager === false) {
            $this->contentProviderManager = $this->container->get('oro_ui.content_provider.manager');
        }

        return $this->contentProviderManager;
    }
}
