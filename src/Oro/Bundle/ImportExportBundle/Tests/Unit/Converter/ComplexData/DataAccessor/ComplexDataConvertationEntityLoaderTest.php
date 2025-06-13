<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData\DataAccessor;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationEntityLoader;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ComplexDataConvertationEntityLoaderTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private OwnershipMetadataProviderInterface&MockObject $ownershipMetadataProvider;
    private ComplexDataConvertationEntityLoader $entityLoader;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->entityLoader = new ComplexDataConvertationEntityLoader(
            $this->doctrine,
            $this->tokenAccessor,
            $this->ownershipMetadataProvider
        );
    }

    public function testLoadEntityWhenEntityHasNoOwner(): void
    {
        $entityClass = 'Test\Entity';
        $criteria = ['field' => 'value'];
        $entity = new \stdClass();

        $ownershipMetadata = new OwnershipMetadata('NONE');
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects(self::never())
            ->method('getOrganizationId');

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($entity);

        self::assertSame($entity, $this->entityLoader->loadEntity($entityClass, $criteria));
    }

    public function testLoadEntityWhenEntityHasOwnerAndThereIsOrganizationInSecurityContext(): void
    {
        $entityClass = 'Test\Entity';
        $criteria = ['field' => 'value'];
        $organizationId = 1;
        $entity = new \stdClass();

        $ownershipMetadata = new OwnershipMetadata('USER', 'user', 'user_id', 'organization', 'org_id');
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn($organizationId);

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(array_merge($criteria, ['organization' => $organizationId]))
            ->willReturn($entity);

        self::assertSame($entity, $this->entityLoader->loadEntity($entityClass, $criteria));
    }

    public function testLoadEntityWhenEntityHasOwnerAndThereIsOrganizationInSecurityContextAndEntityNotFound(): void
    {
        $entityClass = 'Test\Entity';
        $criteria = ['field' => 'value'];
        $organizationId = 1;

        $ownershipMetadata = new OwnershipMetadata('USER', 'user', 'user_id', 'organization', 'org_id');
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn($organizationId);

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(array_merge($criteria, ['organization' => $organizationId]))
            ->willReturn(null);

        self::assertNull($this->entityLoader->loadEntity($entityClass, $criteria));
    }

    public function testLoadEntityWhenEntityHasOwnerAndThereIsNoOrganizationInSecurityContext(): void
    {
        $entityClass = 'Test\Entity';
        $criteria = ['field' => 'value'];

        $ownershipMetadata = new OwnershipMetadata('USER', 'user', 'user_id', 'organization', 'org_id');
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(null);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertNull($this->entityLoader->loadEntity($entityClass, $criteria));
    }
}
