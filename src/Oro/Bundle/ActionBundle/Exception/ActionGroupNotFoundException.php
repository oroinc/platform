<?php

namespace Oro\Bundle\ActionBundle\Exception;

class ActionGroupNotFoundException extends \RuntimeException
{
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct(sprintf('ActionGroup with name "%s" not found', $name));
    }
}
