<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeValueProvider;
use Oro\Bundle\PlatformBundle\Provider\DbalTypeDefaultValueProvider;

class AttributeValueProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ClassMetadata */
    private $classMetadata;

    /** @var AttributeValueProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $queryBuilder = new QueryBuilder($this->entityManager);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $dbalTypeDefaultValueProvider = new DbalTypeDefaultValueProvider();

        $this->provider = new AttributeValueProvider($managerRegistry, $dbalTypeDefaultValueProvider);

        $this->entityManager->expects(self::any())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->entityManager->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());

        $this->classMetadata = new ClassMetadata(\stdClass::class);
        $this->entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($this->classMetadata);
    }

    public function testRemoveAttributeValuesWhenAttributeIsToOneAssociation(): void
    {
        $attributeFamily = new AttributeFamily();
        $names = ['sample_association_1'];

        $this->classMetadata->associationMappings[$names[0]] = ['type' => ClassMetadataInfo::MANY_TO_ONE];

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->onlyMethods(['setParameters', 'execute'])
            ->addMethods(['setFirstResult', 'setMaxResults'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->entityManager->expects(self::once())
            ->method('createQuery')
            ->with(
                'UPDATE SET entity.sample_association_1 = :sample_association_1 '
                . 'WHERE entity.attributeFamily = :attributeFamily'
            )
            ->willReturn($query);

        $query->expects(self::once())
            ->method('setParameters')
            ->with(
                new ArrayCollection([
                    new Parameter('attributeFamily', $attributeFamily),
                    new Parameter(':sample_association_1', null),
                ])
            )
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('setFirstResult')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('execute');

        $this->provider->removeAttributeValues($attributeFamily, $names);
    }

    public function testRemoveAttributeValuesWhenAttributeIsToManyAssociation(): void
    {
        $attributeFamily = new AttributeFamily();
        $names = ['sample_association_1'];

        $this->classMetadata->associationMappings[$names[0]] = ['type' => ClassMetadataInfo::TO_MANY];

        $this->entityManager->expects(self::never())
            ->method('createQuery');

        $this->provider->removeAttributeValues($attributeFamily, $names);
    }

    public function testRemoveAttributeValuesWhenAttributeIsFieldAndNullable(): void
    {
        $attributeFamily = new AttributeFamily();
        $names = ['sample_field_1'];

        $this->classMetadata->fieldMappings[$names[0]] = ['nullable' => true];

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->onlyMethods(['setParameters', 'execute'])
            ->addMethods(['setFirstResult', 'setMaxResults'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->entityManager->expects(self::once())
            ->method('createQuery')
            ->with('UPDATE SET entity.sample_field_1 = :sample_field_1 WHERE entity.attributeFamily = :attributeFamily')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('setParameters')
            ->with(
                new ArrayCollection([
                    new Parameter('attributeFamily', $attributeFamily),
                    new Parameter(':sample_field_1', null),
                ])
            )
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('setFirstResult')
            ->willReturnSelf();

        $query->expects(self::once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('execute');

        $this->provider->removeAttributeValues($attributeFamily, $names);
    }

    public function testRemoveAttributeValuesWhenAttributeIsFieldAndHasDefault(): void
    {
        $attributeFamily = new AttributeFamily();
        $names = ['sample_field_1'];

        $defaultValue = 'sample_value';
        $this->classMetadata->fieldMappings[$names[0]] = ['default' => $defaultValue];

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->onlyMethods(['setParameters', 'execute'])
            ->addMethods(['setFirstResult', 'setMaxResults'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->entityManager->expects(self::once())
            ->method('createQuery')
            ->with('UPDATE SET entity.sample_field_1 = :sample_field_1 WHERE entity.attributeFamily = :attributeFamily')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('setParameters')
            ->with(
                new ArrayCollection([
                    new Parameter('attributeFamily', $attributeFamily),
                    new Parameter(':sample_field_1', $defaultValue),
                ])
            )
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('setFirstResult')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('execute');

        $this->provider->removeAttributeValues($attributeFamily, $names);
    }

    public function testRemoveAttributeValuesWhenAttributeIsFieldAndNoDefaultNotNullable(): void
    {
        $attributeFamily = new AttributeFamily();
        $names = ['sample_field_1'];

        $this->classMetadata->fieldMappings[$names[0]] = ['type' => 'integer'];

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->onlyMethods(['setParameters', 'execute'])
            ->addMethods(['setFirstResult', 'setMaxResults'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->entityManager->expects(self::once())
            ->method('createQuery')
            ->with('UPDATE SET entity.sample_field_1 = :sample_field_1 WHERE entity.attributeFamily = :attributeFamily')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('setParameters')
            ->with(
                new ArrayCollection([
                    new Parameter('attributeFamily', $attributeFamily),
                    new Parameter(':sample_field_1', 0),
                ])
            )
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('setFirstResult')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('execute');

        $this->provider->removeAttributeValues($attributeFamily, $names);
    }

    public function testRemoveAttributeValuesWhenAttributeIsFieldAndNoDefaultNotNullableNoDbalDefault(): void
    {
        $attributeFamily = new AttributeFamily();
        $names = ['sample_field_1'];

        $this->classMetadata->fieldMappings[$names[0]] = ['type' => 'unsupported_type'];

        $this->entityManager->expects(self::never())
            ->method('createQuery');

        $this->provider->removeAttributeValues($attributeFamily, $names);
    }

    public function testRemoveAttributeValuesWhenNoFieldOrAssociation(): void
    {
        $attributeFamily = new AttributeFamily();
        $names = ['sample_field_1'];

        $this->entityManager->expects(self::never())
            ->method('createQuery');

        $this->provider->removeAttributeValues($attributeFamily, $names);
    }
}
