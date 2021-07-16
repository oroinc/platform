<?php

namespace Oro\Bundle\ApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This exception is thrown when a resource was found but it is not accessible through API.
 */
class ResourceNotAccessibleException extends NotFoundHttpException
{
    public function __construct(string $message = 'The resource is not accessible.')
    {
        parent::__construct($message);
    }
}
