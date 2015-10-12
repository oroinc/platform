<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor;

class EntityApiHandlerProcessor
{
    /**
     * @var EntityApiHandlerInterface[]
     */
    protected $handlers = [];

    /**
     * @param EntityApiHandlerInterface $handler
     */
    public function addHandler(EntityApiHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @return EntityApiHandlerInterface[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Get handler by entity class
     *
     * @param string $class
     *
     * @return EntityApiHandlerInterface
     */
    public function getHandlerByClass($class)
    {
        /** @var EntityApiHandlerInterface $handler */
        foreach ($this->handlers as $handler) {
            if ($handler->getClass() === $class) {
                return $handler;
            }
        }

        return null;
    }
}
