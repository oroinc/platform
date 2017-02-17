<?php

namespace Oro\Bundle\FormBundle\Exception;

class UnknownProviderException extends \LogicException
{
    public function __construct($alias)
    {
        parent::__construct(sprintf('Unknown provider with alias `%s`.', $alias));
    }
}
