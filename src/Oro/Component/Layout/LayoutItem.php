<?php

namespace Oro\Component\Layout;

/**
 * Represents an item in a layout.
 */
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

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function getAlias()
    {
        return $this->alias;
    }

    #[\Override]
    public function getTypeName()
    {
        return $this->rawLayoutBuilder->getType($this->id);
    }

    #[\Override]
    public function getOptions()
    {
        return $this->rawLayoutBuilder->getOptions($this->id);
    }

    #[\Override]
    public function getParentId()
    {
        return $this->rawLayoutBuilder->getParentId($this->id);
    }

    #[\Override]
    public function getRootId(): ?string
    {
        $rawLayout = $this->rawLayoutBuilder->getRawLayout();

        return !$rawLayout->isEmpty() ? $rawLayout->getRootId() : null;
    }

    #[\Override]
    public function getContext()
    {
        return $this->context;
    }
}
