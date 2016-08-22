<?php


namespace Oro\Component\Layout;

use Oro\Component\Layout\Model\LayoutUpdateImport;
use Oro\Component\Layout\Exception\LogicException;

class ImportLayoutManipulator implements LayoutManipulatorInterface
{
    const ROOT_PLACEHOLDER = '__root';

    const NAMESPACE_PLACEHOLDER = '__';
    const NAMESPACE_SUFFIX = '_';

    const ADDITIONAL_BLOCK_PREFIX_OPTION = 'additional_block_prefixes';
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
            $rootId = $this->getRoot($this->import);
            if ($rootId === null) {
                throw new LogicException('Import root is not defined.');
            }
            $id = $rootId;
        }

        return $this;
    }

    /**
     * @param LayoutUpdateImport $import
     *
     * @return string
     */
    protected function getRoot(LayoutUpdateImport $import)
    {
        $rootId = $import->getRoot();
        if ($rootId === self::ROOT_PLACEHOLDER && $import->getParent()) {
            $rootId = $this->getRoot($import->getParent());
        }
        if ($this->hasNamespacePlaceholder($rootId) && $import->getParent()) {
            $rootId = $this->getReplacement($rootId, $import->getParent()->getNamespace());
        }
        return $rootId;
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
            $namespace = $this->getNamespace($this->import);
            $replacement = $namespace.self::NAMESPACE_SUFFIX;
            if (!$namespace) {
                $replacement = '';
            }

            $id = substr_replace($id, $replacement, 0, strlen(self::NAMESPACE_PLACEHOLDER));
        }

        return $this;
    }

    /**
     * @param $id
     * @param $namespace
     *
     * @return mixed
     */
    protected function getReplacement($id, $namespace)
    {
        $replacement = $namespace.self::NAMESPACE_SUFFIX;
        if (!$namespace) {
            $replacement = '';
        }
        return substr_replace($id, $replacement, 0, strlen(self::NAMESPACE_PLACEHOLDER));
    }

    /**
     * @param LayoutUpdateImport $import
     *
     * @return string
     */
    protected function getNamespace(LayoutUpdateImport $import)
    {
        $namespace = $import->getNamespace();
        if ($import->getParent()) {
            $namespace = $namespace ? self::NAMESPACE_SUFFIX . $namespace : '';
            $namespace = $this->getNamespace($import->getParent()) . $namespace;
        }
        return $namespace;
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
            $options[self::ADDITIONAL_BLOCK_PREFIX_OPTION] = $this->getAdditionalBlockPrefixes($id, $this->import);
        }

        return $this;
    }

    /**
     * @param string $id
     * @param LayoutUpdateImport $import
     * @param array $prefixes
     *
     * @return array
     */
    protected function getAdditionalBlockPrefixes($id, LayoutUpdateImport $import, $prefixes = [])
    {
        $prefixes[] = sprintf(self::ADDITIONAL_BLOCK_PREFIX_PATTERN, $import->getId(), $id);
        if ($import->getParent()) {
            $prefixes = $this->getAdditionalBlockPrefixes($id, $import->getParent(), $prefixes);
        }
        return $prefixes;
    }
}
