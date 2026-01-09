<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Overrides Symfony Request format mappings to treat application/vnd.api+json as 'json'
 * And disable the 'jsonapi' format mapping introduced by symfony/http-foundation v7.4
 *
 * { @see \Symfony\Component\HttpFoundation\Request::initializeFormats }
 */
class JsonApiFormatOverrideListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 256]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $request->setFormat('jsonapi', []);
        $request->setFormat('json', ['application/json', 'application/x-json', 'application/vnd.api+json']);
    }
}
