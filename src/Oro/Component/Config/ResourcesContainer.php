<?php

namespace Oro\Component\Config;

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * The container for config resources.
 */
class ResourcesContainer implements ResourcesContainerInterface
{
    /** @var ResourceInterface[] [entity class => ResourceInterface, ...] */
    private $resources = [];

    #[\Override]
    public function getResources(): array
    {
        return $this->resources;
    }

    #[\Override]
    public function addResource(ResourceInterface $resource): void
    {
        $this->resources[] = $resource;
    }
}
