<?php


namespace Oro\Component\Layout;


use Oro\Component\Layout\Model\LayoutUpdateImport;

class ImportLayoutManipulator implements LayoutManipulatorInterface
{
    protected $import;

    protected $layoutManipulator;

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
        // TODO: Implement add() method.
        $this->layoutManipulator->add($id, $parentId, $blockType, $options, $siblingId, $prepend);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($id)
    {
        // TODO: Implement remove() method.
        $this->layoutManipulator->remove($id);
    }

    /**
     * {@inheritDoc}
     */
    public function move($id, $parentId = null, $siblingId = null, $prepend = false)
    {
        // TODO: Implement move() method.
        $this->layoutManipulator->move($id, $parentId, $siblingId, $prepend);
    }

    /**
     * {@inheritDoc}
     */
    public function addAlias($alias, $id)
    {
        // TODO: Implement addAlias() method.
        $this->layoutManipulator->addAlias($alias, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function removeAlias($alias)
    {
        // TODO: Implement removeAlias() method.
        $this->layoutManipulator->removeAlias($alias);
    }

    /**
     * {@inheritDoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        // TODO: Implement setOption() method.
        $this->layoutManipulator->setOption($id, $optionName, $optionValue);
    }

    /**
     * {@inheritDoc}
     */
    public function appendOption($id, $optionName, $optionValue)
    {
        // TODO: Implement appendOption() method.
        $this->layoutManipulator->appendOption($id, $optionName, $optionValue);
    }

    /**
     * {@inheritDoc}
     */
    public function subtractOption($id, $optionName, $optionValue)
    {
        // TODO: Implement subtractOption() method.
        $this->layoutManipulator->subtractOption($id, $optionName, $optionValue);
    }

    /**
     * {@inheritDoc}
     */
    public function replaceOption($id, $optionName, $oldOptionValue, $newOptionValue)
    {
        // TODO: Implement replaceOption() method.
        $this->layoutManipulator->replaceOption($id, $optionName, $oldOptionValue, $newOptionValue);
    }

    /**
     * {@inheritDoc}
     */
    public function removeOption($id, $optionName)
    {
        // TODO: Implement removeOption() method.
        $this->layoutManipulator->removeOption($id, $optionName);
    }

    /**
     * {@inheritDoc}
     */
    public function changeBlockType($id, $blockType, $optionsCallback = null)
    {
        // TODO: Implement changeBlockType() method.
        $this->layoutManipulator->changeBlockType($id, $blockType, $optionsCallback);
    }

    /**
     * {@inheritDoc}
     */
    public function setBlockTheme($themes, $id = null)
    {
        // TODO: Implement setBlockTheme() method.
        $this->layoutManipulator->setBlockTheme($themes, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function setFormTheme($themes)
    {
        $this->layoutManipulator->setFormTheme($themes);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->layoutManipulator->clear();
    }
}
