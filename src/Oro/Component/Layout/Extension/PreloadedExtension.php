<?php

namespace Oro\Component\Layout\Extension;

use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\Exception;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;

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
     * Creates a new preloaded extension.
     *
     * @param array $types                BlockTypeInterface[]
     * @param array $typeExtensions       array of BlockTypeExtensionInterface[]
     * @param array $layoutUpdates        array of LayoutUpdateInterface[]
     * @param array $contextConfigurators ContextConfiguratorInterface[]
     * @param array $dataProviders        object[]
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        array $types,
        array $typeExtensions = [],
        array $layoutUpdates = [],
        array $contextConfigurators = [],
        array $dataProviders = []
    ) {
        $this->validateTypes($types);
        $this->validateTypeExtensions($typeExtensions);
        $this->validateLayoutUpdates($layoutUpdates);
        $this->validateContextConfigurators($contextConfigurators);
        $this->validateDataProviders($dataProviders);

        $this->types                = $types;
        $this->typeExtensions       = $typeExtensions;
        $this->layoutUpdates        = $layoutUpdates;
        $this->contextConfigurators = $contextConfigurators;
        $this->dataProviders        = $dataProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
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
    public function hasType($name)
    {
        return isset($this->types[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions($name)
    {
        return isset($this->typeExtensions[$name])
            ? $this->typeExtensions[$name]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypeExtensions($name)
    {
        return !empty($this->typeExtensions[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutUpdates(LayoutItemInterface $item)
    {
        $idOrAlias = $item->getAlias() ?: $item->getId();

        return isset($this->layoutUpdates[$idOrAlias])
            ? $this->layoutUpdates[$idOrAlias]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasLayoutUpdates(LayoutItemInterface $item)
    {
        $idOrAlias = $item->getAlias() ?: $item->getId();

        return !empty($this->layoutUpdates[$idOrAlias]);
    }

    /**
     * {@inheritdoc}
     */
    public function getContextConfigurators()
    {
        return $this->contextConfigurators;
    }

    /**
     * {@inheritdoc}
     */
    public function hasContextConfigurators()
    {
        return !empty($this->contextConfigurators);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataProvider($name)
    {
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
        return isset($this->dataProviders[$name]);
    }

    /**
     * @param array $types
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function validateTypes(array $types)
    {
        foreach ($types as $key => $val) {
            if (!is_string($key)) {
                throw new Exception\InvalidArgumentException(
                    'Keys of $types array must be strings.'
                );
            }
            if (!$val instanceof BlockTypeInterface) {
                throw new Exception\InvalidArgumentException(
                    'Each item of $types array must be BlockTypeInterface.'
                );
            }
        }
    }

    /**
     * @param array $typeExtensions
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function validateTypeExtensions(array $typeExtensions)
    {
        foreach ($typeExtensions as $key => $val) {
            if (!is_string($key)) {
                throw new Exception\InvalidArgumentException(
                    'Keys of $typeExtensions array must be strings.'
                );
            }
            if (!is_array($val)) {
                throw new Exception\InvalidArgumentException(
                    'Each item of $typeExtensions array must be array of BlockTypeExtensionInterface.'
                );
            }
            foreach ($val as $subVal) {
                if (!$subVal instanceof BlockTypeExtensionInterface) {
                    throw new Exception\InvalidArgumentException(
                        'Each item of $typeExtensions[] array must be BlockTypeExtensionInterface.'
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

    /**
     * @param array $contextConfigurators
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function validateContextConfigurators(array $contextConfigurators)
    {
        foreach ($contextConfigurators as $val) {
            if (!$val instanceof ContextConfiguratorInterface) {
                throw new Exception\InvalidArgumentException(
                    'Each item of $contextConfigurators array must be ContextConfiguratorInterface.'
                );
            }
        }
    }

    /**
     * @param array $dataProviders
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function validateDataProviders(array $dataProviders)
    {
        foreach ($dataProviders as $key => $val) {
            if (!is_string($key)) {
                throw new Exception\InvalidArgumentException(
                    'Keys of $dataProviders array must be strings.'
                );
            }
        }
    }
}
