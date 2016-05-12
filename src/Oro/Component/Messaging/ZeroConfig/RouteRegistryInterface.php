<?php
namespace Oro\Component\Messaging\ZeroConfig;

interface RouteRegistryInterface
{
    public function getRoutes($messageName);
}
