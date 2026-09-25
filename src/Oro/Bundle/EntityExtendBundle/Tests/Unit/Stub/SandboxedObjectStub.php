<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Stub;

/**
 * An object exposed to a Twig template: it has a method and a public property, and it is the sandbox security
 * policy, not this class, that decides which of them a template is allowed to access.
 */
class SandboxedObjectStub
{
    public string $secretProp = 'secret-value';

    public function secret(): string
    {
        return 'secret-value';
    }
}
