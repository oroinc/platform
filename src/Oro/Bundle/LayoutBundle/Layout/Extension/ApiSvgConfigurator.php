<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Adds is_svg_via_api parameter to the layout context.
 * Sets to true when request is through API and absolute URLs should be used.
 */
class ApiSvgConfigurator implements ContextConfiguratorInterface
{
    public function __construct(
        private ApiUrlResolver $apiUrlResolver
    ) {
    }

    #[\Override]
    public function configureContext(ContextInterface $context): void
    {
        $context->getResolver()
            ->define('is_svg_via_api')
            ->allowedTypes('bool')
            ->default($this->apiUrlResolver->shouldUseAbsoluteUrls());
    }
}
