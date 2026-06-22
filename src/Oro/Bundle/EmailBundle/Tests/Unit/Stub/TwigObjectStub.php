<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

/**
 * Minimal object stub for Twig attribute access tests.
 */
class TwigObjectStub
{
    public string $propertyOnly = 'property_value';

    public function getValue(): string
    {
        return 'method_value';
    }
}
