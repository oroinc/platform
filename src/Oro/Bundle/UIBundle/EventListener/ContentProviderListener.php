<?php

namespace Oro\Bundle\UIBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface;
use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;

class ContentProviderListener
{
    /**
     * @var ContentProviderManager
     */
    protected $contentProviderManager;

    /**
     * Constructor.
     *
     * @param ContentProviderManager $contentProviderManager
     */
    public function __construct(ContentProviderManager $contentProviderManager)
    {
        $this->contentProviderManager = $contentProviderManager;
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
                        $this->contentProviderManager->enableContentProvider($name);
                    }
                }

                if ($displayContentProviders) {
                    $displayContentProviders = explode(',', $displayContentProviders);
                    /** @var ContentProviderInterface $provider */
                    foreach ($this->contentProviderManager->getContentProviders() as $provider) {
                        if (!in_array($provider->getName(), $displayContentProviders)) {
                            $provider->setEnabled(false);
                        }
                    }
                }
            }
        }
    }
}
