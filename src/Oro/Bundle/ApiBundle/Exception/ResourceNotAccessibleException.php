<?php

namespace Oro\Bundle\ApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This exception thrown if the resource was found but it is not accessible through Data API.
 */
class ResourceNotAccessibleException extends NotFoundHttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct('The resource is not accessible.');
    }
}
