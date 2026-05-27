<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntityDraftSyncReferenceResolverTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;
    private EntityDraftSyncReferenceResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->resolver = new EntityDraftSyncReferenceResolver($this->doctrine);
    }

    public function testGetReferenceReturnsNullWhenEntityIsNull(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $result = $this->resolver->getReference(null);

        self::assertNull($result);
    }

    public function testGetReferenceReturnsEntityWhenAlreadyManaged(): void
    {
        $entity = new EntityDraftAwareStub();

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(EntityDraftAwareStub::class)
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($entity)
            ->willReturn(true);

        $result = $this->resolver->getReference($entity);

        self::assertSame($entity, $result);
    }

    public function testGetReferenceReturnsEntityWhenNotManagedAndHasNoIdentifier(): void
    {
        $entity = new EntityDraftAwareStub();
        // No ID set — getIdentifierValues returns an empty array

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn([]);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(EntityDraftAwareStub::class)
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($entity)
            ->willReturn(false);

        $this->entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(EntityDraftAwareStub::class)
            ->willReturn($classMetadata);

        $this->entityManager->expects(self::never())
            ->method('getReference');

        $result = $this->resolver->getReference($entity);

        self::assertSame($entity, $result);
    }

    public function testGetReferenceReturnsDoctrineReferenceWhenNotManagedAndHasIdentifier(): void
    {
        $entity = new EntityDraftAwareStub();
        ReflectionUtil::setId($entity, 42);

        $proxy = new EntityDraftAwareStub();

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn([42]);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(EntityDraftAwareStub::class)
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($entity)
            ->willReturn(false);

        $this->entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(EntityDraftAwareStub::class)
            ->willReturn($classMetadata);

        $this->entityManager->expects(self::once())
            ->method('getReference')
            ->with(EntityDraftAwareStub::class, 42)
            ->willReturn($proxy);

        $result = $this->resolver->getReference($entity);

        self::assertSame($proxy, $result);
    }

    public function testGetEnumReferenceReturnsNullWhenNull(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $result = $this->resolver->getEnumReference(null);

        self::assertNull($result);
    }

    public function testGetEnumReferenceReturnsDoctrineReferenceFromStringId(): void
    {
        $enumId = 'order_status.open';
        $returnedEnum = new EnumOption('order_status', 'Open', 'open');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(EnumOption::class)
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::once())
            ->method('getReference')
            ->with(EnumOption::class, $enumId)
            ->willReturn($returnedEnum);

        $result = $this->resolver->getEnumReference($enumId);

        self::assertSame($returnedEnum, $result);
    }

    public function testGetEnumReferenceResolvesEnumOptionObjectViaGetReference(): void
    {
        $enumOption = new EnumOption('order_status', 'Open', 'open');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(EnumOption::class)
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($enumOption)
            ->willReturn(true);

        $result = $this->resolver->getEnumReference($enumOption);

        self::assertSame($enumOption, $result);
    }
}
