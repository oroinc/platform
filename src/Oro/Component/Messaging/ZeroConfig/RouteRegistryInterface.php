<?php
namespace Oro\Component\Messaging\ZeroConfig;

interface RouteRegistryInterface
{
    /**
     * @param string $messageName
     * 
     * @return Route[]
     */
    public function getRoutes($messageName);
}
