<?php

namespace Oro\Component\Layout;

final class LayoutItem implements LayoutItemInterface
{
    /** @var ContextInterface */
    private $context;

    /** @var RawLayoutBuilderInterface */
    private $rawLayoutBuilder;

    /** @var string */
    private $id;

    /** @var string|null */
    private $alias;

    /**
     * @param RawLayoutBuilderInterface $rawLayoutBuilder
     * @param ContextInterface          $context
     */
    public function __construct(
        RawLayoutBuilderInterface $rawLayoutBuilder,
        ContextInterface $context
    ) {
        $this->rawLayoutBuilder = $rawLayoutBuilder;
        $this->context          = $context;
    }

    /**
     * Initializes the state of this object
     *
     * @param string      $id    The layout item id
     * @param string|null $alias The layout item alias
     */
    public function initialize($id, $alias = null)
    {
        $this->id    = $id;
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeName()
    {
        return $this->rawLayoutBuilder->getType($this->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->rawLayoutBuilder->getOptions($this->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getParentId()
    {
        return $this->rawLayoutBuilder->getParentId($this->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }
}
