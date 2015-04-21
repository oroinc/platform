<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service that could be used in order to associate interface name as a dependency injector
 * setter for object of any type
 */
class DependencyInitializer
{
    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $knownDependencies = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $interface
     * @param string $setterMethod
     * @param string $serviceId
     */
    public function addKnownDependency($interface, $setterMethod, $serviceId)
    {
        $this->knownDependencies[$interface] = [$setterMethod, $serviceId];
    }

    /**
     * Initializes object dependencies
     *
     * @param object $object
     */
    public function initialize($object)
    {
        if (!is_object($object)) {
            return;
        }

        foreach ($this->knownDependencies as $interface => $dependencyInfo) {
            list($setterMethod, $serviceId) = $dependencyInfo;

            if (is_a($object, $interface)) {
                $object->$setterMethod($this->container->get($serviceId));
            }
        }
    }
}
