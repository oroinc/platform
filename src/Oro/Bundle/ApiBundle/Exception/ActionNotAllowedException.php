<?php

namespace Oro\Bundle\ApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This exception is thrown when the resource was found but the requested action is not allowed.
 */
class ActionNotAllowedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(405, 'The action is not allowed.');
    }
}
