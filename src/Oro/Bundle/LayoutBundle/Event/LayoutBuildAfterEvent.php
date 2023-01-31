<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Event;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after {@see Layout} is built.
 */
class LayoutBuildAfterEvent extends Event
{
    private Layout $layout;

    private LayoutBuilderInterface $layoutBuilder;

    public function __construct(Layout $layout, LayoutBuilderInterface $layoutBuilder)
    {
        $this->layout = $layout;
        $this->layoutBuilder = $layoutBuilder;
    }

    public function getLayout(): Layout
    {
        return $this->layout;
    }

    public function getLayoutBuilder(): LayoutBuilderInterface
    {
        return $this->layoutBuilder;
    }
}
