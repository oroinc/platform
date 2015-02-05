<?php

namespace Oro\Component\Layout;

/**
 * An extension with preloaded block types, block type exceptions and layout updates.
 */
class PreloadedExtension implements ExtensionInterface
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
     * Creates a new preloaded extension.
     *
     * @param array $blockTypes          BlockTypeInterface[]
     * @param array $blockTypeExtensions array of BlockTypeExtensionInterface[]
     * @param array $layoutUpdates       array of LayoutUpdateInterface[]
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $blockTypes, array $blockTypeExtensions = [], array $layoutUpdates = [])
    {
        $this->validateBlockTypes($blockTypes);
        $this->validateBlockTypeExtensions($blockTypeExtensions);
        $this->validateLayoutUpdates($layoutUpdates);

        $this->types          = $blockTypes;
        $this->typeExtensions = $blockTypeExtensions;
        $this->layoutUpdates  = $layoutUpdates;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockType($name)
    {
        if (!isset($this->types[$name])) {
            throw new Exception\InvalidArgumentException(
                sprintf('The type "%s" can not be loaded by this extension.', $name)
            );
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasBlockType($name)
    {
        return isset($this->types[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockTypeExtensions($name)
    {
        return isset($this->typeExtensions[$name])
            ? $this->typeExtensions[$name]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasBlockTypeExtensions($name)
    {
        return !empty($this->typeExtensions[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutUpdates($id)
    {
        return isset($this->layoutUpdates[$id])
            ? $this->layoutUpdates[$id]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasLayoutUpdates($id)
    {
        return !empty($this->layoutUpdates[$id]);
    }

    /**
     * @param array $blockTypes
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function validateBlockTypes(array $blockTypes)
    {
        foreach ($blockTypes as $key => $val) {
            if (!is_string($key)) {
                throw new Exception\InvalidArgumentException(
                    'Keys of $blockTypes array must be strings.'
                );
            }
            if (!$val instanceof BlockTypeInterface) {
                throw new Exception\InvalidArgumentException(
                    'Each item of $blockTypes array must be BlockTypeInterface.'
                );
            }
        }
    }

    /**
     * @param array $blockTypeExtensions
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function validateBlockTypeExtensions(array $blockTypeExtensions)
    {
        foreach ($blockTypeExtensions as $key => $val) {
            if (!is_string($key)) {
                throw new Exception\InvalidArgumentException(
                    'Keys of $blockTypeExtensions array must be strings.'
                );
            }
            if (!is_array($val)) {
                throw new Exception\InvalidArgumentException(
                    'Each item of $blockTypeExtensions array must be array of BlockTypeExtensionInterface.'
                );
            }
            foreach ($val as $subVal) {
                if (!$subVal instanceof BlockTypeExtensionInterface) {
                    throw new Exception\InvalidArgumentException(
                        'Each item of $blockTypeExtensions[] array must be BlockTypeExtensionInterface.'
                    );
                }
            }
        }
    }

    /**
     * @param array $layoutUpdates
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function validateLayoutUpdates(array $layoutUpdates)
    {
        foreach ($layoutUpdates as $key => $val) {
            if (!is_string($key)) {
                throw new Exception\InvalidArgumentException(
                    'Keys of $layoutUpdates array must be strings.'
                );
            }
            if (!is_array($val)) {
                throw new Exception\InvalidArgumentException(
                    'Each item of $layoutUpdates array must be array of LayoutUpdateInterface.'
                );
            }
            foreach ($val as $subVal) {
                if (!$subVal instanceof LayoutUpdateInterface) {
                    throw new Exception\InvalidArgumentException(
                        'Each item of $layoutUpdates[] array must be LayoutUpdateInterface.'
                    );
                }
            }
        }
    }
}
