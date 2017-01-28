<?php

namespace Oro\Bundle\ApiBundle\Exception;

use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

/**
 * This exception thrown if the resource was found but it is not accessible through Data API.
 */
class ResourceNotAccessibleException extends ForbiddenException implements ExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct('The resource is not accessible.');
    }
}
