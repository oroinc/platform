<?php

namespace Oro\Bundle\ActionBundle\Exception;

use Oro\Component\Action\Exception\RuntimeException;

/**
 * Thrown when a requested operation is not found in the operation registry.
 */
class OperationNotFoundException extends RuntimeException
{
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct(sprintf('Operation with name "%s" not found', $name));
    }
}
