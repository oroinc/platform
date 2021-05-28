<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Enables content providers based on "_enableContentProviders" and "_displayContentProviders" request parameters.
 */
class ContentProviderListener implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $contentProvidersToEnable = $request->get('_enableContentProviders');
        $displayContentProviders = $request->get('_displayContentProviders');
        if (!$contentProvidersToEnable && !$displayContentProviders) {
            return;
        }

        /** @var ContentProviderManager $contentProviderManager */
        $contentProviderManager = $this->container->get('oro_ui.content_provider.manager');
        if ($contentProvidersToEnable) {
            $contentProvidersToEnable = explode(',', $contentProvidersToEnable);
            foreach ($contentProvidersToEnable as $name) {
                $contentProviderManager->enableContentProvider($name);
            }
        }
        if ($displayContentProviders) {
            $displayContentProviders = explode(',', $displayContentProviders);
            $providerNames = $contentProviderManager->getContentProviderNames();
            foreach ($providerNames as $name) {
                if (!\in_array($name, $displayContentProviders, true)) {
                    $contentProviderManager->disableContentProvider($name);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'oro_ui.content_provider.manager' => ContentProviderManager::class
        ];
    }
}
