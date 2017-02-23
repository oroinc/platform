<?php

namespace Oro\Bundle\FormBundle\Exception;

class UnknownProviderException extends \LogicException
{
    /**
     * @param string $alias
     * @param \Exception $parent
     */
    public function __construct($alias, \Exception $parent = null)
    {
        parent::__construct(sprintf('Unknown provider with alias `%s`.', $alias), 0, $parent);
    }
}
