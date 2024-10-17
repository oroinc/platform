<?php

declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Tools;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Sets/gets URL in the router context which is used when a real request is not available.
 */
class RouterRequestContextManipulator
{
    public function __construct(
        private RequestContext $context,
        private PropertyAccessorInterface $propertyAccessor
    ) {
    }

    public function setRouterContextFromUrl(string $url): void
    {
        $urlParts = parse_url($url);
        if ($urlParts === false) {
            return;
        }

        if (isset($urlParts['scheme'])) {
            $this->context->setScheme($urlParts['scheme']);
        }

        if (isset($urlParts['host'])) {
            $this->context->setHost($urlParts['host']);
        }

        if (isset($urlParts['port'])) {
            if ($this->context->getScheme() === 'https') {
                $this->context->setHttpsPort($urlParts['port']);
            } else {
                $this->context->setHttpPort($urlParts['port']);
            }
        }

        if (isset($urlParts['path'])) {
            $this->context->setBaseUrl($urlParts['path']);
        }
    }

    public function setRouterContextState(array $contextState): void
    {
        foreach ($contextState as $key => $value) {
            $this->propertyAccessor->setValue($this->context, $key, $value);
        }
    }

    public function getRouterContextState(): array
    {
        $scheme = $this->context->getScheme();

        $state = [
            'scheme' => $scheme,
            'host' => $this->context->getHost(),
            'baseUrl' => $this->context->getBaseUrl(),
        ];

        if ($scheme === 'https') {
            $state['httpsPort'] = $this->context->getHttpsPort();
        } else {
            $state['httpPort'] = $this->context->getHttpPort();
        }

        return $state;
    }
}
