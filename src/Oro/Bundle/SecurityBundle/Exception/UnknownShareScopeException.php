<?php

namespace Oro\Bundle\SecurityBundle\Exception;

class UnknownShareScopeException extends \Exception
{
    public function __construct($scope)
    {
        parent::__construct(sprintf('Unknown share scope "%s"', $scope));
    }
}
