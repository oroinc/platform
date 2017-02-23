<?php

namespace Oro\Bundle\FormBundle\Exception;

class UnknownFormHandlerException extends \LogicException
{
    /**
     * @param string $alias
     * @param \Exception $parent
     */
    public function __construct($alias, \Exception $parent = null)
    {
        parent::__construct(sprintf('Unknown form handler with alias `%s`.', $alias), 0, $parent);
    }
}
