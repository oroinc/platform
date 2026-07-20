<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Stub;

/**
 * Stub entity with a `category` property used to test getter-to-property name resolution
 * in {@see \Oro\Bundle\EntityBundle\Twig\Analyzer\DoctrineTypeResolver}.
 */
class EntityWithCategoryPropertyStub
{
    public string $category = '';

    public string $name = '';
}
