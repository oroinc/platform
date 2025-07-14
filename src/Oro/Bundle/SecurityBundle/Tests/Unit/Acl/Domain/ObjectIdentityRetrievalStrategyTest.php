<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityRetrievalStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;

class ObjectIdentityRetrievalStrategyTest extends TestCase
{
    public function testGetObjectIdentityWithString(): void
    {
        $factory = $this->createMock(ObjectIdentityFactory::class);
        $strategy = new ObjectIdentityRetrievalStrategy($factory);

        $result = new ObjectIdentity('id', 'type');
        $factory->expects($this->once())
            ->method('get')
            ->with($this->equalTo('test'))
            ->willReturn($result);

        $this->assertSame($result, $strategy->getObjectIdentity('test'));
    }

    public function testGetObjectIdentityWithObject(): void
    {
        $factory = $this->createMock(ObjectIdentityFactory::class);
        $strategy = new ObjectIdentityRetrievalStrategy($factory);

        $obj = new \stdClass();
        $result = new ObjectIdentity('id', 'type');
        $factory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($obj))
            ->willReturn($result);

        $this->assertSame($result, $strategy->getObjectIdentity($obj));
    }

    public function testGetObjectIdentityShouldCatchInvalidDomainObjectException(): void
    {
        $factory = $this->createMock(ObjectIdentityFactory::class);
        $strategy = new ObjectIdentityRetrievalStrategy($factory);

        $obj = new \stdClass();
        $factory->expects($this->once())
            ->method('get')
            ->willThrowException(new InvalidDomainObjectException());

        $this->assertNull($strategy->getObjectIdentity($obj));
    }
}
