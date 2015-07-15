<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension\Stub;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

class DomainObjectStub implements DomainObjectInterface
{
    const IDENTIFIER = 'identifier';

    /**
     * {@inheritDoc}
     */
    public function getObjectIdentifier()
    {
        return self::IDENTIFIER;
    }
}
