<?php

namespace Oro\Component\Layout\Extension\DependencyInjection;

use Oro\Component\Layout\Exception;
use Oro\Component\Layout\Extension\ExtensionInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Psr\Container\ContainerInterface;

/**
 * Handles access to layout block types, extensions, layout updates, context configurators
 */
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
     * The data provider services registered in DI container
     *
     * @var string[]
     */
    private $dataProviderServiceIds;

    /**
     * @param ContainerInterface $container
     * @param string[]           $typeServiceIds
     * @param array              $typeExtensionServiceIds array of string[]
     * @param array              $layoutUpdateServiceIds  array of string[]
     * @param string[]           $contextConfiguratorServiceIds
     * @param string[]           $dataProviderServiceIds
     */
    public function __construct(
        ContainerInterface $container,
        array $typeServiceIds,
        array $typeExtensionServiceIds,
        array $layoutUpdateServiceIds,
        array $contextConfiguratorServiceIds,
        array $dataProviderServiceIds
    ) {
        $this->container                     = $container;
        $this->typeServiceIds                = $typeServiceIds;
        $this->typeExtensionServiceIds       = $typeExtensionServiceIds;
        $this->layoutUpdateServiceIds        = $layoutUpdateServiceIds;
        $this->contextConfiguratorServiceIds = $contextConfiguratorServiceIds;
        $this->dataProviderServiceIds        = $dataProviderServiceIds;
    }

    #[\Override]
    public function getTypeNames(): array
    {
        return array_keys($this->typeServiceIds);
    }

    #[\Override]
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

    #[\Override]
    public function hasType($name)
    {
        return isset($this->typeServiceIds[$name]);
    }

    #[\Override]
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

    #[\Override]
    public function hasTypeExtensions($name)
    {
        return isset($this->typeExtensionServiceIds[$name]);
    }

    #[\Override]
    public function getLayoutUpdates(LayoutItemInterface $item)
    {
        $idOrAlias     = $item->getAlias() ?: $item->getId();
        $layoutUpdates = [];

        if (isset($this->layoutUpdateServiceIds[$idOrAlias])) {
            foreach ($this->layoutUpdateServiceIds[$idOrAlias] as $serviceId) {
                $layoutUpdates[] = $this->container->get($serviceId);
            }
        }

        return $layoutUpdates;
    }

    #[\Override]
    public function hasLayoutUpdates(LayoutItemInterface $item)
    {
        $idOrAlias = $item->getAlias() ?: $item->getId();

        return isset($this->layoutUpdateServiceIds[$idOrAlias]);
    }

    #[\Override]
    public function getContextConfigurators()
    {
        $configurators = [];

        foreach ($this->contextConfiguratorServiceIds as $serviceId) {
            $configurators[] = $this->container->get($serviceId);
        }

        return $configurators;
    }

    #[\Override]
    public function hasContextConfigurators()
    {
        return !empty($this->contextConfiguratorServiceIds);
    }

    #[\Override]
    public function getDataProvider($name)
    {
        if (!isset($this->dataProviderServiceIds[$name])) {
            throw new Exception\InvalidArgumentException(
                sprintf('The data provider "%s" is not registered with the service container.', $name)
            );
        }

        return $this->container->get($this->dataProviderServiceIds[$name]);
    }

    #[\Override]
    public function hasDataProvider($name)
    {
        return isset($this->dataProviderServiceIds[$name]);
    }
}
