<?php

namespace Oro\Bundle\PlatformBundle\Provider;

use Psr\Http\Message\RequestInterface;

/**
 * Provide list of page requests.
 */
interface PageRequestProviderInterface
{
    /**
     * @return RequestInterface[]
     */
    public function getRequests(): array;
}
