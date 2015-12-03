<?php

namespace Oro\Bundle\ApiBundle\Exceptioin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BadSorterValueHttpException extends HttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(Response::HTTP_NOT_ACCEPTABLE, $message, $previous, [], $code);
    }
}
