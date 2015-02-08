<?php

namespace Oro\Component\Layout;

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
    public function getLayoutUpdates($id)
    {
        if (null === $this->layoutUpdates) {
            $this->initLayoutUpdates();
        }

        return !empty($this->layoutUpdates[$id])
            ? $this->layoutUpdates[$id]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasLayoutUpdates($id)
    {
        if (null === $this->layoutUpdates) {
            $this->initLayoutUpdates();
        }

        return !empty($this->layoutUpdates[$id]);
    }

    /**
     * Registers block types.
     *
     * @return BlockTypeInterface[]
     */
    protected function loadBlockTypes()
    {
        return [];
    }

    /**
     * Registers block type extensions.
     *
     * @return array of BlockTypeExtensionInterface[]
     */
    protected function loadBlockTypeExtensions()
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
     * @return array of array of LayoutUpdateInterface
     */
    protected function loadLayoutUpdates()
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

        foreach ($this->loadBlockTypes() as $type) {
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

        foreach ($this->loadBlockTypeExtensions() as $extension) {
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
     * @throws Exception\UnexpectedTypeException if any registered layout update is not
     *                                           an instance of LayoutUpdateInterface
     *                                           or layout item id is not a string
     */
    private function initLayoutUpdates()
    {
        $loadedLayoutUpdates = $this->loadLayoutUpdates();
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
}
