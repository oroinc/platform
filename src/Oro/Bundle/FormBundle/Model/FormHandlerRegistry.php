<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Exception\UnknownFormHandlerException;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;

class FormHandlerRegistry
{
    /** @var FormHandlerInterface[] */
    private $handlers = [];

    /**
     * @param string $alias
     *
     * @return FormHandlerInterface
     */
    public function get($alias)
    {
        if (!isset($this->handlers[$alias])) {
            throw new UnknownFormHandlerException($alias);
        }

        return $this->handlers[$alias];
    }

    /**
     * @param FormHandlerInterface $handler
     *
     * @return $this
     */
    public function addHandler(FormHandlerInterface $handler)
    {
        $this->handlers[$handler->getAlias()] = $handler;

        return $this;
    }

    /**
     * @param $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        return isset($this->handlers[$alias]);
    }
}
