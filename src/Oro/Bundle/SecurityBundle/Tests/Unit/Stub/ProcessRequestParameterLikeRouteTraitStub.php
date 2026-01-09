<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Stub;

use Oro\Bundle\SecurityBundle\Authentication\Handler\ProcessRequestParameterLikeRouteTrait;
use Psr\Log\LoggerInterface;

/**
 * ProcessRequestParameterLikeRouteTrait stub for testing purposes
 */
class ProcessRequestParameterLikeRouteTraitStub
{
    use ProcessRequestParameterLikeRouteTrait {
        ProcessRequestParameterLikeRouteTrait::processRequestParameter as public processRequestParameterPublic;
    }

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
