<?php

namespace Oro\Component\Layout;

class LayoutManager
{
    /** @var LayoutFactoryBuilderInterface */
    protected $layoutFactoryBuilder;

    /**
     * @param LayoutFactoryBuilderInterface $layoutFactoryBuilder
     */
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
