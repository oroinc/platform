<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Stub;

/**
 * Stub entity with a snake_case property used to test camelCase-to-snake_case property name fallback
 * in {@see DoctrineTypeResolver}.
 */
class EntityWithSnakeCasePropertyStub
{
    // phpcs:ignore Oro.NamingConventions.ConstantName
    public string $password_expires_at = '';
}
