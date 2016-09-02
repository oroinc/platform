<?php

namespace Oro\Component\Layout\Extension;

use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\Exception;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;

abstract class AbstractExtension implements ExtensionInterface
{
    /**
     * The block types provided by this extension
     *
     * @var BlockTypeInterface[]
     *
     * Example:
     *  [
     *      'block_type_1' => BlockTypeInterface,
     *      'block_type_2' => BlockTypeInterface
     *  ]
     */
    private $types;

    /**
     * The block type extensions provided by this extension
     *
     * @var array of BlockTypeExtensionInterface[]
     *
     * Example:
     *  [
     *      'block_type_1' => array of BlockTypeExtensionInterface,
     *      'block_type_2' => array of BlockTypeExtensionInterface
     *  ]
     */
    private $typeExtensions;

    /**
     * The layout updates provided by this extension
     *
     * @var array of LayoutUpdateInterface[]
     *
     * Example:
     *  [
     *      'item_1' => array of LayoutUpdateInterface,
     *      'item_2' => array of LayoutUpdateInterface
     *  ]
     */
    private $layoutUpdates;

    /**
     * The layout context configurators provided by this extension
     *
     * @var ContextConfiguratorInterface[]
     */
    private $contextConfigurators;

    /**
     * The data providers provided by this extension
     *
     * @var object[]
     */
    private $dataProviders;

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (null === $this->types) {
            $this->initTypes();
        }

        if (!isset($this->types[$name])) {
            throw new Exception\InvalidArgumentException(
                sprintf('The block type "%s" can not be loaded by this extension.', $name)
            );
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        if (null === $this->types) {
            $this->initTypes();
        }

        return isset($this->types[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions($name)
    {
        if (null === $this->typeExtensions) {
            $this->initTypeExtensions();
        }

        return !empty($this->typeExtensions[$name])
            ? $this->typeExtensions[$name]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypeExtensions($name)
    {
        if (null === $this->typeExtensions) {
            $this->initTypeExtensions();
        }

        return !empty($this->typeExtensions[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutUpdates(LayoutItemInterface $item)
    {
        $idOrAlias = $item->getAlias() ? : $item->getId();

        if (null === $this->layoutUpdates) {
            $this->initLayoutUpdates($item->getContext());
        }

        return !empty($this->layoutUpdates[$idOrAlias])
            ? $this->layoutUpdates[$idOrAlias]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasLayoutUpdates(LayoutItemInterface $item)
    {
        $idOrAlias = $item->getAlias() ?: $item->getId();

        if (null === $this->layoutUpdates) {
            $this->initLayoutUpdates($item->getContext());
        }

        return !empty($this->layoutUpdates[$idOrAlias]);
    }

    /**
     * {@inheritdoc}
     */
    public function getContextConfigurators()
    {
        if (null === $this->contextConfigurators) {
            $this->initContextConfigurators();
        }

        return $this->contextConfigurators;
    }

    /**
     * {@inheritdoc}
     */
    public function hasContextConfigurators()
    {
        if (null === $this->contextConfigurators) {
            $this->initContextConfigurators();
        }

        return !empty($this->contextConfigurators);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataProvider($name)
    {
        if (null === $this->dataProviders) {
            $this->initDataProviders();
        }

        if (!isset($this->dataProviders[$name])) {
            throw new Exception\InvalidArgumentException(
                sprintf('The data provider "%s" can not be loaded by this extension.', $name)
            );
        }

        return $this->dataProviders[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasDataProvider($name)
    {
        if (null === $this->dataProviders) {
            $this->initDataProviders();
        }

        return isset($this->dataProviders[$name]);
    }

    /**
     * Registers block types.
     *
     * @return BlockTypeInterface[]
     */
    protected function loadTypes()
    {
        return [];
    }

    /**
     * Registers block type extensions.
     *
     * @return BlockTypeExtensionInterface[]
     */
    protected function loadTypeExtensions()
    {
        return [];
    }

    /**
     * Registers layout updates.
     *
     * Example of returned array:
     *  [
     *      'itemId1' => [layoutUpdate1, layoutUpdate2],
     *      'itemId2' => [layoutUpdate3]
     *  ]
     *
     * @param ContextInterface $context
     *
     * @return array of array of LayoutUpdateInterface
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        return [];
    }

    /**
     * Registers layout context configurators.
     *
     * @return ContextConfiguratorInterface[]
     */
    protected function loadContextConfigurators()
    {
        return [];
    }

    /**
     * Registers data providers.
     *
     * Example of returned array:
     *  [
     *      'dataProvider1' => dataProvider1,
     *      'dataProvider2' => dataProvider2
     *  ]
     *
     * @return object[]
     */
    protected function loadDataProviders()
    {
        return [];
    }

    /**
     * Initializes block types.
     *
     * @throws Exception\UnexpectedTypeException if any registered block type is not
     *                                           an instance of BlockTypeInterface
     */
    private function initTypes()
    {
        $this->types = [];

        foreach ($this->loadTypes() as $type) {
            if (!$type instanceof BlockTypeInterface) {
                throw new Exception\UnexpectedTypeException(
                    $type,
                    'Oro\Component\Layout\BlockTypeInterface'
                );
            }

            $this->types[$type->getName()] = $type;
        }
    }

    /**
     * Initializes block type extensions.
     *
     * @throws Exception\UnexpectedTypeException if any registered block type extension is not
     *                                           an instance of BlockTypeExtensionInterface
     */
    private function initTypeExtensions()
    {
        $this->typeExtensions = [];

        foreach ($this->loadTypeExtensions() as $extension) {
            if (!$extension instanceof BlockTypeExtensionInterface) {
                throw new Exception\UnexpectedTypeException(
                    $extension,
                    'Oro\Component\Layout\BlockTypeExtensionInterface'
                );
            }

            $type = $extension->getExtendedType();

            $this->typeExtensions[$type][] = $extension;
        }
    }

    /**
     * Initializes layout updates.
     *
     * @param ContextInterface $context
     *
     * @throws Exception\UnexpectedTypeException if any registered layout update is not
     *                                           an instance of LayoutUpdateInterface
     *                                           or layout item id is not a string
     */
    private function initLayoutUpdates(ContextInterface $context)
    {
        $loadedLayoutUpdates = $this->loadLayoutUpdates($context);
        foreach ($loadedLayoutUpdates as $id => $layoutUpdates) {
            if (!is_string($id)) {
                throw new Exception\UnexpectedTypeException(
                    $id,
                    'string',
                    'layout item id'
                );
            }
            if (!is_array($layoutUpdates)) {
                throw new Exception\UnexpectedTypeException(
                    $layoutUpdates,
                    'array',
                    sprintf('layout updates for item "%s"', $id)
                );
            }
            foreach ($layoutUpdates as $layoutUpdate) {
                if (!$layoutUpdate instanceof LayoutUpdateInterface) {
                    throw new Exception\UnexpectedTypeException(
                        $layoutUpdate,
                        'Oro\Component\Layout\LayoutUpdateInterface'
                    );
                }
            }
        }
        $this->layoutUpdates = $loadedLayoutUpdates;
    }

    /**
     * Initializes layout context configurators.
     *
     * @throws Exception\UnexpectedTypeException if any registered context configurators is not
     *                                           an instance of ContextConfiguratorInterface
     */
    private function initContextConfigurators()
    {
        $this->contextConfigurators = [];

        foreach ($this->loadContextConfigurators() as $configurator) {
            if (!$configurator instanceof ContextConfiguratorInterface) {
                throw new Exception\UnexpectedTypeException(
                    $configurator,
                    'Oro\Component\Layout\ContextConfiguratorInterface'
                );
            }

            $this->contextConfigurators[] = $configurator;
        }
    }

    /**
     * Initializes data providers.
     */
    private function initDataProviders()
    {
        $this->dataProviders = [];

        foreach ($this->loadDataProviders() as $name => $dataProvider) {
            $this->dataProviders[$name] = $dataProvider;
        }
    }
}
