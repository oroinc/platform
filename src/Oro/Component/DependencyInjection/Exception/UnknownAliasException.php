<?php

namespace Oro\Component\DependencyInjection\Exception;

class UnknownAliasException extends \LogicException
{
    /**
     * @param string $alias
     * @param \Exception|null $parent
     */
    public function __construct($alias, \Exception $parent = null)
    {
        parent::__construct(sprintf('Unknown service link alias `%s`', $alias), 0, $parent);
    }
}
