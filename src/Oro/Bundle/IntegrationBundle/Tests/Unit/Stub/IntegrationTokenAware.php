<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;

use Oro\Bundle\IntegrationBundle\Authentication\Token\IntegrationTokenAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * IntegrationTokenAwareTrait stub
 */
class IntegrationTokenAware
{
    use IntegrationTokenAwareTrait;

    public function __construct()
    {
        $this->tokenStorage = new TokenStorage();
    }
}
