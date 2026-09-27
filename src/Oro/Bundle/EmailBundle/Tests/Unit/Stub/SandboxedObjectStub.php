<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

/**
 * An object exposed to a Twig template: it has a method, a property and one more method, and it is the sandbox
 * security policy, not this class, that decides which of them a template is allowed to access.
 */
class SandboxedObjectStub
{
    public string $secretProp = 'secret-value';

    public function secret(): string
    {
        return 'secret-value';
    }

    public function getAllowed(): string
    {
        return 'allowed-value';
    }
}
