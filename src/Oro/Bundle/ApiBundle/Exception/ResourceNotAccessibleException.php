<?php

namespace Oro\Bundle\ApiBundle\Exception;

use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

/**
 * The resource was found but it is accessible through Data API.
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
