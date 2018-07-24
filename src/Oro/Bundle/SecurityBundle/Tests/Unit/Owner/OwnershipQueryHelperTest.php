<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnershipQueryHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class OwnershipQueryHelperTest extends OrmTestCase
{
    /** @var EntityManager */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityClassResolver */
    protected $entityClassResolver;

    /** @var OwnershipQueryHelper */
    protected $ownershipQueryHelper;

    public function setUp()
    {
        $reader = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity'
            ]
        );

        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);

        $this->entityClassResolver->expects(self::any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entityName) {
                return 0 === strpos($entityName, 'Test:')
                    ? 'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity' . substr($entityName, 5)
                    : $entityName;
            });

        $this->ownershipQueryHelper = new OwnershipQueryHelper(
            $this->ownershipMetadataProvider,
            $this->entityClassResolver
        );
    }

    /**
     * @param string|null $organizationFieldName
     * @param string|null $ownerFieldName
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataInterface
     */
    protected function getOwnershipMetadata($organizationFieldName = null, $ownerFieldName = null)
    {
        $metadata = $this->createMock(OwnershipMetadataInterface::class);
        $metadata->expects(self::any())
            ->method('hasOwner')
            ->willReturn(null !== $organizationFieldName || null !== $ownerFieldName);
        $metadata->expects(self::any())
            ->method('getOrganizationFieldName')
            ->willReturn($organizationFieldName);
        $metadata->expects(self::any())
            ->method('getOwnerFieldName')
            ->willReturn($ownerFieldName);

        return $metadata;
    }

    public function testNoIdFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name');

        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata')
            ->with($entityClass);

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT e.name FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdFieldWithExprInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id + 1 AS id');

        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata')
            ->with($entityClass);

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT e.id + 1 AS id FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdFieldWithFunctionInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('NULLIF(e.id, e.id1) AS id');

        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata')
            ->with($entityClass);

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT NULLIF(e.id, e.id1) AS id FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithoutAliasInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id  AS  idAlias');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'idAlias', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id  AS  idAlias,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithoutAliasAsFirstFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id, e.name');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id, e.name,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasAsFirstFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id AS id, e.name');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id AS id, e.name,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithoutAliasAsLastFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasAsLastFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id AS id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id AS id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithoutAliasAsMiddleFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id, e.name AS name1');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id, e.name AS name1,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasAsMiddleFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id AS id, e.name AS name1');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id AS id, e.name AS name1,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testFieldStartsWithIdStringAsFirstFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id1, e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id1, e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testFieldStartsWithIdStringAsLastFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id1');

        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata')
            ->with($entityClass);

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id1 FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testFieldStartsWithIdStringAsMiddleFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id1, e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id1, e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasInSelectWhenAsIsLowercase()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id as id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id as id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testNoOwnerField()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', null));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', null]
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testNoOrganizationField()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata(null, 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', null, 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testEntityWithoutOwnership()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata(null, null));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT e.id FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testOwnershipFieldsAlreadyExistInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::exactly(2))
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        // add ownership fields
        $this->ownershipQueryHelper->addOwnershipFields($qb);

        // test that ownership fields are not duplicated
        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }
}
