<?php

namespace Oro\Component\ConfigExpression\Extension\DependencyInjection;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DependencyInjectionExtension implements ExtensionInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var string[] */
    protected $serviceIds;

    /**
     * @param ContainerInterface $container
     * @param string[]           $serviceIds
     */
    public function __construct(ContainerInterface $container, array $serviceIds)
    {
        $this->container  = $container;
        $this->serviceIds = $serviceIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression($name)
    {
        if (!isset($this->serviceIds[$name])) {
            throw new Exception\InvalidArgumentException(
                sprintf('The expression "%s" is not registered with the service container.', $name)
            );
        }

        return $this->container->get($this->serviceIds[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasExpression($name)
    {
        return isset($this->serviceIds[$name]);
    }

    /**
     * @return string[]
     */
    public function getServiceIds()
    {
        return $this->serviceIds;
    }
}
