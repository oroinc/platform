<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SecurityBundle\Provider\PermissionsPolicyHeaderProvider;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Set Permissions-Policy based on the oro_security.permissions_policy configuration.
 */
class PermissionsPolicyHeaderRequestListener
{
    public function __construct(
        private PermissionsPolicyHeaderProvider $headerProvider
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->headerProvider->isEnabled()) {
            return;
        }

        $event->getResponse()->headers->set('Permissions-Policy', $this->headerProvider->getDirectivesValue());
    }
}
