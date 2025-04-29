<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\Contact as TestTargetEntity;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\ContactEmail as TestEntity;
use Oro\Bundle\FormBundle\Validator\Constraints\UnchangeableField;
use Oro\Bundle\FormBundle\Validator\Constraints\UnchangeableFieldValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class UnchangeableFieldValidatorTest extends ConstraintValidatorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private EntityManagerInterface&MockObject $em;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->willReturn($this->em);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): UnchangeableFieldValidator
    {
        return new UnchangeableFieldValidator($this->doctrineHelper);
    }

    private function getClassMetadata(
        string $entityClass,
        bool $isIdentifierComposite = false
    ): ClassMetadata&MockObject {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = $entityClass;
        $metadata->isIdentifierComposite = $isIdentifierComposite;

        return $metadata;
    }

    private function expectOriginalEntityData(object $object, string $propertyName, mixed $returnValue): void
    {
        $uow = $this->createMock(UnitOfWork::class);
        $this->em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('getOriginalEntityData')
            ->with(self::identicalTo($object))
            ->willReturn([$propertyName => $returnValue]);
    }

    /**
     * @dataProvider changedFieldValueDataProvider
     */
    public function testValidateWhenFieldValueHasChanged(mixed $oldValue, mixed $newValue): void
    {
        $object = new TestEntity();
        $fieldName = 'email';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(false);

        $this->expectOriginalEntityData($object, $fieldName, $oldValue);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $fieldName);
        $this->validator->validate($newValue, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function changedFieldValueDataProvider(): array
    {
        return [
            'changed value' => [
                'old value' => 'old value',
                'new value' => 'new value'
            ],
            'changed value is null' => [
                'old value' => 'old value',
                'new value' => null
            ]
        ];
    }

    /**
     * @dataProvider notChangedFieldValueDataProvider
     */
    public function testValidateWhenFieldValueHasNotChanged(mixed $oldValue, mixed $newValue): void
    {
        $object = new TestEntity();
        $fieldName = 'email';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(false);

        $this->expectOriginalEntityData($object, $fieldName, $oldValue);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $fieldName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function notChangedFieldValueDataProvider(): array
    {
        return [
            'not changed value' => [
                'old value' => 'value',
                'new value' => 'value'
            ],
            'not changed value is null' => [
                'old value' => null,
                'new value' => null
            ],
            'old value was null' => [
                'old value' => null,
                'new value' => 'value'
            ]
        ];
    }

    public function testValidateWhenAllowResetAndFieldValueHasChangedToNull(): void
    {
        $object = new TestEntity();
        $fieldName = 'email';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(false);

        $this->expectOriginalEntityData($object, $fieldName, 'value');

        $constraint = new UnchangeableField(['allowReset' => true]);
        $this->setProperty($object, $fieldName);
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAssociationValueHasChanged(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = ['id' => 1];
        $newValue = new TestTargetEntity();
        $newValueId = ['id' => 2];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::exactly(2))
            ->method('getIdentifierValues')
            ->willReturnMap([
                [$oldValue, $oldValueId],
                [$newValue, $newValueId]
            ]);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidateWhenAssociationValueHasChangedToNull(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = ['id' => 1];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with(self::identicalTo($oldValue))
            ->willReturn($oldValueId);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate(null, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidateWhenAssociationValueHasNotChanged(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = ['id' => 1];
        $newValue = new TestTargetEntity();
        $newValueId = ['id' => 1];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::exactly(2))
            ->method('getIdentifierValues')
            ->willReturnMap([
                [$oldValue, $oldValueId],
                [$newValue, $newValueId]
            ]);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAssociationValueHasNotChangedAndValuesAreNull(): void
    {
        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::never())
            ->method('getIdentifierValues');

        $this->expectOriginalEntityData($object, $associationName, null);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAssociationValueWasNull(): void
    {
        $newValue = new TestTargetEntity();

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::never())
            ->method('getIdentifierValues');

        $this->expectOriginalEntityData($object, $associationName, null);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAssociationValueHasInvalidType(): void
    {
        $newValue = 'new value';

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::never())
            ->method('getAssociationMapping');
        $metadata->expects(self::never())
            ->method('getIdentifierValues');

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAssociationValueHasInvalidTypeOfObject(): void
    {
        $newValue = new \stdClass();

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $metadata->expects(self::never())
            ->method('getIdentifierValues');

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAssociationValueIsNewEntity(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = ['id' => 1];
        $newValue = new TestTargetEntity();
        $newValueId = [];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::exactly(2))
            ->method('getIdentifierValues')
            ->willReturnMap([
                [$oldValue, $oldValueId],
                [$newValue, $newValueId]
            ]);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidateWhenAssociationOldValueIsNewEntity(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = [];
        $newValue = new TestTargetEntity();

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($oldValue)
            ->willReturn($oldValueId);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAssociationValuesAreNewEntities(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = [];
        $newValue = new TestTargetEntity();

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($oldValue)
            ->willReturn($oldValueId);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenTargetEntityHasCompositeIdentifier(): void
    {
        $newValue = new TestTargetEntity();

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class, true);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            '%s is not allowed to be used for %s::%s because the target entity %s has composite identifier.',
            UnchangeableFieldValidator::class,
            TestEntity::class,
            $associationName,
            TestTargetEntity::class
        ));

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAllowResetAndAssociationValueHasChangedToNull(): void
    {
        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::never())
            ->method('getIdentifierValues');

        $this->expectOriginalEntityData($object, $associationName, new TestTargetEntity());

        $constraint = new UnchangeableField(['allowReset' => true]);
        $this->setProperty($object, $associationName);
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenCollectionFieldValueWasNotChanged(): void
    {
        $newValue = new PersistentCollection(
            $this->em,
            $this->getClassMetadata(TestEntity::class),
            new ArrayCollection([new TestEntity(), new TestEntity()])
        );
        $newValue->takeSnapshot();

        $object = new TestTargetEntity();
        $object->setId(12);
        $associationName = 'emails';
        $metadata = $this->getClassMetadata(TestTargetEntity::class);

        $metadata->expects(self::once())
            ->method('getIdentifierValues')
            ->willReturnCallback(function ($entity) {
                return [$entity->getId()];
            });
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['type' => ClassMetadata::ONE_TO_MANY]);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestTargetEntity::class)
            ->willReturn($metadata);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenCollectionValuedAssociationValueWasChanged(): void
    {
        $uow = $this->createMock(UnitOfWork::class);
        $this->em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $newValue = new PersistentCollection(
            $this->em,
            $this->getClassMetadata(TestEntity::class),
            new ArrayCollection([new TestEntity(), new TestEntity()])
        );
        $newValue->takeSnapshot();
        $newValue->add(new TestEntity());

        $object = new TestTargetEntity();
        $object->setId(12);
        $associationName = 'emails';
        $metadata = $this->getClassMetadata(TestTargetEntity::class);

        $metadata->expects(self::once())
            ->method('getIdentifierValues')
            ->willReturnCallback(function ($entity) {
                return [$entity->getId()];
            });
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['type' => ClassMetadata::ONE_TO_MANY]);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestTargetEntity::class)
            ->willReturn($metadata);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidateWithDisallowChangeOwnerWhenAssociationValueHasChanged(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = ['id' => 1];
        $newValue = new TestTargetEntity();
        $newValueId = ['id' => 2];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::exactly(2))
            ->method('getIdentifierValues')
            ->willReturnMap([
                [$oldValue, $oldValueId],
                [$newValue, $newValueId]
            ]);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidateWithDisallowChangeOwnerWhenAssociationValueHasChangedToNull(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = ['id' => 1];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with(self::identicalTo($oldValue))
            ->willReturn($oldValueId);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate(null, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidateWithDisallowChangeOwnerWhenAssociationValueHasNotChanged(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = ['id' => 1];
        $newValue = new TestTargetEntity();
        $newValueId = ['id' => 1];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::exactly(4))
            ->method('getIdentifierValues')
            ->willReturnMap([
                [$oldValue, $oldValueId],
                [$newValue, $newValueId]
            ]);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithDisallowChangeOwnerWhenAssociationValueHasNotChangedValuesAreNull(): void
    {
        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::never())
            ->method('getIdentifierValues');

        $this->expectOriginalEntityData($object, $associationName, null);

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithDisallowChangeOwnerWhenManyToOneAssociationValueWasNull(): void
    {
        $newValue = new TestTargetEntity();
        $newValueId = ['id' => 2];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($newValue)
            ->willReturn($newValueId);

        $this->expectOriginalEntityData($object, $associationName, null);

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithDisallowChangeOwnerWhenOneToOneAssociationValueWasNull(): void
    {
        $newValue = new TestTargetEntity();
        $newValueId = ['id' => 2];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::ONE_TO_ONE]);
        $targetMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($newValue)
            ->willReturn($newValueId);

        $this->expectOriginalEntityData($object, $associationName, null);

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidateWithDisallowChangeOwnerWhenAssociationValueHasInvalidType(): void
    {
        $newValue = 'new value';

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::never())
            ->method('getAssociationMapping');
        $metadata->expects(self::never())
            ->method('getIdentifierValues');

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithDisallowChangeOwnerWhenAssociationValueHasInvalidTypeOfObject(): void
    {
        $newValue = new \stdClass();

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $metadata->expects(self::never())
            ->method('getIdentifierValues');

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithDisallowChangeOwnerWhenAssociationValueIsNewEntity(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = ['id' => 1];
        $newValue = new TestTargetEntity();
        $newValueId = [];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::exactly(3))
            ->method('getIdentifierValues')
            ->willReturnMap([
                [$oldValue, $oldValueId],
                [$newValue, $newValueId]
            ]);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidateWithDisallowChangeOwnerWhenAssociationOldValueIsNewEntity(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = [];
        $newValue = new TestTargetEntity();
        $newValueId = ['id' => 2];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::exactly(2))
            ->method('getIdentifierValues')
            ->willReturnMap([
                [$oldValue, $oldValueId],
                [$newValue, $newValueId]
            ]);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidateWithDisallowChangeOwnerWhenAssociationValuesAreNewEntities(): void
    {
        $oldValue = new TestTargetEntity();
        $oldValueId = [];
        $newValue = new TestTargetEntity();
        $newValueId = [];

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);
        $targetMetadata = $this->getClassMetadata(TestTargetEntity::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $metadata],
                [TestTargetEntity::class, $targetMetadata]
            ]);
        $metadata->expects(self::once())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with($associationName)
            ->willReturn(['targetEntity' => TestTargetEntity::class, 'type' => ClassMetadata::MANY_TO_ONE]);
        $targetMetadata->expects(self::exactly(2))
            ->method('getIdentifierValues')
            ->willReturnMap([
                [$oldValue, $oldValueId],
                [$newValue, $newValueId]
            ]);

        $this->expectOriginalEntityData($object, $associationName, $oldValue);

        $constraint = new UnchangeableField(['allowChangeOwner' => false]);
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }
}
