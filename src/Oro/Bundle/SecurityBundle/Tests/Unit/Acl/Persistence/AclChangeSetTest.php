<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclChangeSet;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

final class AclChangeSetTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $changeSet = new AclChangeSet();

        self::assertTrue($changeSet->isEmpty());
        self::assertFalse($changeSet->isChanged('Acme\Test'));
    }

    public function testAddChangedOid(): void
    {
        $changeSet = new AclChangeSet();
        $changeSet->addChangedOid(new ObjectIdentity('entity', 'Acme\Test'));
        $changeSet->addChangedOid(new ObjectIdentity('entity', 'Acme\Test'));

        self::assertFalse($changeSet->isEmpty());
        self::assertTrue($changeSet->isChanged('Acme\Test'));
        self::assertFalse($changeSet->isChanged('Acme\Other'));
    }
}
