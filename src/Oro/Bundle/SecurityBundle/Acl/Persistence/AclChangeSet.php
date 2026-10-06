<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface as OID;

/**
 * Collects object identities whose ACLs have been actually changed by {@see AclManager::flush()}.
 */
final class AclChangeSet
{
    /** @var array [oid type => true, ...] */
    private array $changedOidTypes = [];

    public function addChangedOid(OID $oid): void
    {
        $this->changedOidTypes[$oid->getType()] = true;
    }

    public function isChanged(string $oidType): bool
    {
        return isset($this->changedOidTypes[$oidType]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->changedOidTypes;
    }
}
