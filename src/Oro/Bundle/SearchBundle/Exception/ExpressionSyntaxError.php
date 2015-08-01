<?php

namespace Oro\Bundle\SearchBundle\Exception;

class ExpressionSyntaxError extends \LogicException
{
    public function __construct($message, $cursor = 0)
    {
        parent::__construct(sprintf('%s around position %d.', $message, $cursor));
    }
}
