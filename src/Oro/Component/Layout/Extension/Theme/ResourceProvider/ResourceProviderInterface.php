<?php

namespace Oro\Component\Layout\Extension\Theme\ResourceProvider;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ResourceProviderInterface
{
    /**
     * @return array
     */
    public function getResources();

    /**
     * Load resources using container builder
     *
     * @param ContainerBuilder $container The container builder
     *                                    If NULL the loaded resources will not be registered in the container
     *                                    and as result will not be monitored for changes
     * @param array $resources
     *
     * @return array
     */
    public function loadResources(ContainerBuilder $container = null, array $resources = []);
    
    /**
     * Filters applicable resources by paths
     *
     * @param array $paths
     *
     * @return array
     */
    public function findApplicableResources(array $paths);
}
