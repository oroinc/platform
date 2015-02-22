<?php

namespace Oro\Component\Layout\Extension\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\Layout\Exception;
use Oro\Component\Layout\Extension\ExtensionInterface;

class DependencyInjectionExtension implements ExtensionInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * The block type services registered in DI container
     *
     * @var string[]
     *
     * Example:
     *  [
     *      'block_type_1' => 'service1',
     *      'block_type_2' => 'service2'
     *  ]
     */
    private $typeServiceIds;

    /**
     * The block type extension services registered in DI container
     *
     * @var array of string[]
     *
     * Example:
     *  [
     *      'block_type_1' => array of strings,
     *      'block_type_2' => array of strings
     *  ]
     */
    private $typeExtensionServiceIds;

    /**
     * The layout update services registered in DI container
     *
     * @var array of string[]
     *
     * Example:
     *  [
     *      'item_1' => array of strings,
     *      'item_2' => array of strings
     *  ]
     */
    private $layoutUpdateServiceIds;

    /**
     * The context configurator services registered in DI container
     *
     * @var string[]
     */
    private $contextConfiguratorServiceIds;

    /**
     * @param ContainerInterface $container
     * @param string[]           $typeServiceIds
     * @param array              $typeExtensionServiceIds array of string[]
     * @param array              $layoutUpdateServiceIds  array of string[]
     * @param string[]           $contextConfiguratorServiceIds
     */
    public function __construct(
        ContainerInterface $container,
        array $typeServiceIds,
        array $typeExtensionServiceIds,
        array $layoutUpdateServiceIds,
        array $contextConfiguratorServiceIds
    ) {
        $this->container                     = $container;
        $this->typeServiceIds                = $typeServiceIds;
        $this->typeExtensionServiceIds       = $typeExtensionServiceIds;
        $this->layoutUpdateServiceIds        = $layoutUpdateServiceIds;
        $this->contextConfiguratorServiceIds = $contextConfiguratorServiceIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (!isset($this->typeServiceIds[$name])) {
            throw new Exception\InvalidArgumentException(
                sprintf('The block type "%s" is not registered with the service container.', $name)
            );
        }

        $type = $this->container->get($this->typeServiceIds[$name]);

        if ($type->getName() !== $name) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'The type name specified for the service "%s" does not match the actual name. '
                    . 'Expected "%s", given "%s".',
                    $this->typeServiceIds[$name],
                    $name,
                    $type->getName()
                )
            );
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        return isset($this->typeServiceIds[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions($name)
    {
        $extensions = [];

        if (isset($this->typeExtensionServiceIds[$name])) {
            foreach ($this->typeExtensionServiceIds[$name] as $serviceId) {
                $extensions[] = $this->container->get($serviceId);
            }
        }

        return $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypeExtensions($name)
    {
        return isset($this->typeExtensionServiceIds[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutUpdates($id)
    {
        $layoutUpdates = [];

        if (isset($this->layoutUpdateServiceIds[$id])) {
            foreach ($this->layoutUpdateServiceIds[$id] as $serviceId) {
                $layoutUpdates[] = $this->container->get($serviceId);
            }
        }

        return $layoutUpdates;
    }

    /**
     * {@inheritdoc}
     */
    public function hasLayoutUpdates($id)
    {
        return isset($this->layoutUpdateServiceIds[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function getContextConfigurators()
    {
        $configurators = [];

        foreach ($this->contextConfiguratorServiceIds as $serviceId) {
            $configurators[] = $this->container->get($serviceId);
        }

        return $configurators;
    }

    /**
     * {@inheritdoc}
     */
    public function hasContextConfigurators()
    {
        return !empty($this->contextConfiguratorServiceIds);
    }
}
