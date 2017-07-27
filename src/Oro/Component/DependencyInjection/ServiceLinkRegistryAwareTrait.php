<?php

namespace Oro\Component\DependencyInjection;

trait ServiceLinkRegistryAwareTrait
{
    /** @var ServiceLinkRegistry */
    protected $serviceLinkRegistry;

    /**
     * @param ServiceLinkRegistry $serviceLinkAliasRegistry
     */
    public function setServiceLinkRegistry(ServiceLinkRegistry $serviceLinkAliasRegistry)
    {
        $this->serviceLinkRegistry = $serviceLinkAliasRegistry;
    }

    /**
     * @param $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        return $this->serviceLinkRegistry->has($alias);
    }
}
