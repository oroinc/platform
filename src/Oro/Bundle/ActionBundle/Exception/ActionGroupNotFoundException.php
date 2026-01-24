<?php

namespace Oro\Bundle\ActionBundle\Exception;

/**
 * Thrown when a requested action group is not found in the action group registry.
 */
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
