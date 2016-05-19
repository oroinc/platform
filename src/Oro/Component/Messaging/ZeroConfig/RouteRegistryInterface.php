<?php
namespace Oro\Component\Messaging\ZeroConfig;

interface RouteRegistryInterface
{
    /**
     * @param string $topicName
     * 
     * @return Route[]
     */
    public function getRoutes($topicName);
}
