<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\EntityConfigPass;

/**
 * Entity config pass stub for testing purposes
 */
class EntityConfigPassStub extends EntityConfigPass
{
    #[\Override]
    protected function getAppConfigPath(): string
    {
        return '../../config/oro/entity';
    }
}
