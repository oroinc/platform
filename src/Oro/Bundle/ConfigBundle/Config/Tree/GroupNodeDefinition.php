<?php

namespace Oro\Bundle\ConfigBundle\Config\Tree;

use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Responsible for parameters that can be used to build a configuration form in the system configuration.
 */
class GroupNodeDefinition extends AbstractNodeDefinition implements \Countable, \IteratorAggregate
{
    /** @var array */
    protected $children = array();

    /** @var int */
    protected $level = 0;

    public function __construct($name, $definition = array(), $children = array())
    {
        parent::__construct($name, $definition);
        $this->children = $children;
    }

    /**
     * Setter for nesting level
     *
     * @param int $level
     *
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Getter for nesting level
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    #[\Override]
    public function count(): int
    {
        return count($this->children);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        $this->resort();

        return new \ArrayIterator($this->children);
    }

    public function isEmpty()
    {
        return !$this->children;
    }

    /**
     * Returns first child
     *
     * @return AbstractNodeDefinition
     */
    public function first()
    {
        $this->resort();

        return reset($this->children);
    }

    /**
     * Resort children array
     *
     * @return void
     */
    public function resort()
    {
        ArrayUtil::sortBy($this->children, true);
    }

    /**
     * Retrieve block config from group node definition
     *
     * @return array
     */
    public function toBlockConfig()
    {
        return array(
            $this->getName() => array_intersect_key(
                $this->definition,
                array_flip([
                    'title',
                    'priority',
                    'description',
                    'description_style',
                    'configurator',
                    'handler',
                    'page_reload',
                    'tooltip'
                ])
            )
        );
    }

    /**
     * Returns needed definition values to view
     *
     * @return array
     */
    public function toViewData()
    {
        return array_intersect_key(
            $this->definition,
            array_flip(
                ['title', 'priority', 'description', 'description_style',  'icon', 'tooltip']
            )
        );
    }
}
