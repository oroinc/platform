<?php

namespace Oro\Bundle\ActionBundle\Exception;

class OperationNotFoundException extends \Exception
{
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct(sprintf('Operation with name "%s" not found', $name));
    }
}
