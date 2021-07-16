<?php

namespace Oro\Bundle\ApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This exception is thrown when the resource was found but the requested action is not allowed.
 */
class ActionNotAllowedException extends HttpException
{
    public function __construct(string $message = 'The action is not allowed.')
    {
        parent::__construct(Response::HTTP_METHOD_NOT_ALLOWED, $message);
    }
}
