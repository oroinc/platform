<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Component\Layout\ContextInterface;

/**
 * Holds the current layout context instance for access throughout the application.
 *
 * This service acts as a registry for the active layout context, allowing any
 * component to retrieve the current context without passing it through method
 * parameters. It is typically used in event listeners and other services that
 * need access to layout context information.
 */
class LayoutContextHolder
{
    /** @var ContextInterface|null */
    protected $context;

    public function getContext()
    {
        return $this->context;
    }

    public function setContext(ContextInterface $context)
    {
        $this->context = $context;
    }
}
