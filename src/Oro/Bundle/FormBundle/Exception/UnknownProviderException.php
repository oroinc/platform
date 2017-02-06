<?php

namespace Oro\Bundle\FormBundle\Exception;

class UnknownProviderException extends \LogicException
{
    public function __construct($alias)
    {
        $message = sprintf('Unknown provider with alias `%s`.', $alias);
        parent::__construct($message);
    }
}
