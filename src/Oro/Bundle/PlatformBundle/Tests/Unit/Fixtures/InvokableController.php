<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures;

/**
 * Invokable controller fixture for ArgumentResolver tests
 */
class InvokableController
{
    public function __invoke(TestEntity $entity): void
    {
    }
}
