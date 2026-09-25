<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

/**
 * A stub object that can be coerced to a string, used to trigger the Twig sandbox string-coercion check.
 */
class StringableStub implements \Stringable
{
    public function __construct(private readonly string $value = 'secret-value')
    {
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value;
    }
}
