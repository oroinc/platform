<?php

namespace Oro\Component\Layout;

/**
 * Stack that controls the lifecycle of layout contexts.
 */
class LayoutContextStack
{
    protected array $contexts = [];

    public function push(?ContextInterface $context): void
    {
        $this->contexts[] = $context;
    }

    public function pop(): ?ContextInterface
    {
        if (!$this->contexts) {
            return null;
        }

        return array_pop($this->contexts);
    }

    public function getCurrentContext(): ?ContextInterface
    {
        return end($this->contexts) ?: null;
    }

    public function getMainContext(): ?ContextInterface
    {
        if (!$this->contexts) {
            return null;
        }

        return $this->contexts[0];
    }

    public function getParentContext()
    {
        $pos = \count($this->contexts) - 2;

        return $this->contexts[$pos] ?? null;
    }
}
