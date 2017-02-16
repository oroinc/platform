<?php

namespace Oro\Bundle\FormBundle\Exception;

class UnknownFormHandlerException extends \LogicException
{
    /**
     * @param string $alias
     */
    public function __construct($alias)
    {
        parent::__construct(sprintf('Unknown form handler with alias `%s`.', $alias));
    }
}
