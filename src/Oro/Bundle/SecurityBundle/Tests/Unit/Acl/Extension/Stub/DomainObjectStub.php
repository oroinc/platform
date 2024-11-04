<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension\Stub;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

class DomainObjectStub implements DomainObjectInterface
{
    public const IDENTIFIER = 'identifier';

    #[\Override]
    public function getObjectIdentifier()
    {
        return self::IDENTIFIER;
    }
}
