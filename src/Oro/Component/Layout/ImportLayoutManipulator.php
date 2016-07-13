<?php


namespace Oro\Component\Layout;

use Oro\Component\Layout\Model\LayoutUpdateImport;
use Oro\Component\Layout\Exception\LogicException;

class ImportLayoutManipulator implements LayoutManipulatorInterface
{
    const ROOT_PLACEHOLDER = '__root';

    const NAMESPACE_PLACEHOLDER = '__';
    const NAMESPACE_SUFFIX = '_';

    const ADDITIONAL_BLOCK_PREFIX_OPTION = 'additional_block_prefix';
    const ADDITIONAL_BLOCK_PREFIX_PATTERN = '__%s%s';

    /**
     * @var LayoutManipulatorInterface
     */
    protected $layoutManipulator;

    /**
     * @var LayoutUpdateImport
     */
    protected $import;

    /**
     * @param LayoutManipulatorInterface $layoutManipulator
     * @param LayoutUpdateImport $import
     */
    public function __construct(LayoutManipulatorInterface $layoutManipulator, LayoutUpdateImport $import)
    {
        $this->layoutManipulator = $layoutManipulator;
        $this->import = $import;
    }

    /**
     * {@inheritDoc}
     */
    public function add(
        $id,
        $parentId,
        $blockType,
        array $options = [],
        $siblingId = null,
        $prepend = false
    ) {
        $this->addAdditionalBlockPrefixOption($id, $options);

        $this
            ->replaceRoot($parentId)
            ->replaceRoot($siblingId);

        $this
            ->replaceNamespace($id)
            ->replaceNamespace($parentId)
            ->replaceNamespace($siblingId);

        $this->layoutManipulator->add($id, $parentId, $blockType, $options, $siblingId, $prepend);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($id)
    {
        $this->replaceRoot($id);
        $this->replaceNamespace($id);

        $this->layoutManipulator->remove($id);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function move($id, $parentId = null, $siblingId = null, $prepend = false)
    {
        $this
            ->replaceRoot($id)
            ->replaceRoot($parentId)
            ->replaceRoot($siblingId);
        $this
            ->replaceNamespace($id)
            ->replaceNamespace($parentId)
            ->replaceNamespace($siblingId);

        $this->layoutManipulator->move($id, $parentId, $siblingId, $prepend);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addAlias($alias, $id)
    {
        $this->replaceRoot($id);
        $this
            ->replaceNamespace($alias)
            ->replaceNamespace($id);

        $this->layoutManipulator->addAlias($alias, $id);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeAlias($alias)
    {
        $this->replaceNamespace($alias);

        $this->layoutManipulator->removeAlias($alias);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        $this->replaceRoot($id);
        $this->replaceNamespace($id);

        $this->layoutManipulator->setOption($id, $optionName, $optionValue);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function appendOption($id, $optionName, $optionValue)
    {
        $this->replaceRoot($id);
        $this->replaceNamespace($id);

        $this->layoutManipulator->appendOption($id, $optionName, $optionValue);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function subtractOption($id, $optionName, $optionValue)
    {
        $this->replaceRoot($id);
        $this->replaceNamespace($id);

        $this->layoutManipulator->subtractOption($id, $optionName, $optionValue);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function replaceOption($id, $optionName, $oldOptionValue, $newOptionValue)
    {
        $this->replaceRoot($id);
        $this->replaceNamespace($id);

        $this->layoutManipulator->replaceOption($id, $optionName, $oldOptionValue, $newOptionValue);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeOption($id, $optionName)
    {
        $this->replaceRoot($id);
        $this->replaceNamespace($id);

        $this->layoutManipulator->removeOption($id, $optionName);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function changeBlockType($id, $blockType, $optionsCallback = null)
    {
        $this->replaceRoot($id);
        $this->replaceNamespace($id);

        $this->layoutManipulator->changeBlockType($id, $blockType, $optionsCallback);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setBlockTheme($themes, $id = null)
    {
        $this->replaceRoot($id);
        $this->replaceNamespace($id);

        $this->layoutManipulator->setBlockTheme($themes, $id);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormTheme($themes)
    {
        $this->layoutManipulator->setFormTheme($themes);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->layoutManipulator->clear();

        return $this;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    protected function replaceRoot(&$id)
    {
        if (null !== $id && $id === self::ROOT_PLACEHOLDER) {
            $rootId = $this->import->getRoot();
            if ($rootId === null) {
                throw new LogicException('Import root is not defined.');
            }
            $id = $rootId;
        }

        return $this;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    protected function hasNamespacePlaceholder($id)
    {
        return strpos($id, self::NAMESPACE_PLACEHOLDER) === 0 && $id !== self::ROOT_PLACEHOLDER;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    protected function replaceNamespace(&$id)
    {
        if ($this->hasNamespacePlaceholder($id)) {
            $namespace = $this->import->getNamespace();
            if ($namespace === null) {
                throw new LogicException(sprintf('Import namespace for block "%s" is not defined.', $id));
            }

            $id = substr_replace($id, $namespace.self::NAMESPACE_SUFFIX, 0, strlen(self::NAMESPACE_PLACEHOLDER));
        }

        return $this;
    }

    /**
     * @param string $id
     * @param array $options
     *
     * @return $this
     */
    protected function addAdditionalBlockPrefixOption($id, array &$options)
    {
        if ($this->hasNamespacePlaceholder($id)) {
            $options[self::ADDITIONAL_BLOCK_PREFIX_OPTION] = sprintf(
                self::ADDITIONAL_BLOCK_PREFIX_PATTERN,
                $this->import->getId(),
                $id
            );
        }

        return $this;
    }
}
