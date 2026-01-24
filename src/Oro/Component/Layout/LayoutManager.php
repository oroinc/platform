<?php

namespace Oro\Component\Layout;

/**
 * Provides convenient access to layout builders and factories.
 *
 * This manager acts as a facade to the layout factory builder, simplifying access to layout builders
 * and the layout factory itself for clients that need to build and manipulate layouts.
 */
class LayoutManager
{
    /** @var LayoutFactoryBuilderInterface */
    protected $layoutFactoryBuilder;

    public function __construct(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $this->layoutFactoryBuilder = $layoutFactoryBuilder;
    }

    /**
     * @return LayoutBuilderInterface
     */
    public function getLayoutBuilder()
    {
        return $this->layoutFactoryBuilder->getLayoutFactory()->createLayoutBuilder();
    }

    /**
     * @return LayoutFactoryInterface
     */
    public function getLayoutFactory()
    {
        return $this->layoutFactoryBuilder->getLayoutFactory();
    }
}
