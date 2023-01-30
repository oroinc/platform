<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception is thrown when a configuration cannot be loaded due to an unsupported operation is requested
 * and this should be represented as a validation error in API response.
 */
class NotSupportedConfigOperationException extends \RuntimeException implements ValidationExceptionInterface
{
    private string $className;
    private string $operation;

    /**
     * @param string $className The class name of an entity the config is loaded for
     * @param string $operation The name of a config operation that cannot be satisfied
     */
    public function __construct(string $className, string $operation)
    {
        parent::__construct(sprintf(
            'Requested unsupported operation "%s" when building config for "%s".',
            $operation,
            $className
        ));
        $this->className = $className;
        $this->operation = $operation;
    }

    /**
     * Gets the class name of an entity the config is loaded for.
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Gets the name of a config operation that cannot be satisfied.
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
}
