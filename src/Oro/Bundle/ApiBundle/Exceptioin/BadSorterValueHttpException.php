<?php

namespace Oro\Bundle\ApiBundle\Exceptioin;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BadSorterValueHttpException extends HttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(406, $message, $previous, [], $code);
    }
}
