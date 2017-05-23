<?php

namespace Oro\Bundle\EmailBundle\Exception;

class UnknownVariableProcessorException extends \LogicException
{
    /**
     * @param string $alias
     * @param \Exception $parent
     */
    public function __construct($alias, \Exception $parent = null)
    {
        parent::__construct(sprintf('Unknown variable processor with alias `%s`.', $alias), 0, $parent);
    }
}
