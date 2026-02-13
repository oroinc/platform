<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Stub;

/**
 * Test entity used for validator metadata loading.
 */
class PersistentValidatorCacheWarmerTestEntity
{
    public ?string $name = null;

    public function isOptional(): bool
    {
        return false;
    }

    public function getType(): string
    {
        return 'test';
    }
}
