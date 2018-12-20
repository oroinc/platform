<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\Contact as TestTargetEntity;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\ContactEmail as TestEntity;
use Oro\Bundle\FormBundle\Validator\Constraints\UnchangeableField;
use Oro\Bundle\FormBundle\Validator\Constraints\UnchangeableFieldValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UnchangeableFieldValidatorTest extends ConstraintValidatorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->willReturn($this->em);

        parent::setUp();
    }

    protected function createValidator()
    {
        return new UnchangeableFieldValidator($this->doctrineHelper);
    }

    /**
     * @param string $entityClass
     * @param bool   $isIdentifierComposite
     *
     * @return ClassMetadata|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getClassMetadata(string $entityClass, bool $isIdentifierComposite = false)
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = $entityClass;
        $metadata->isIdentifierComposite = $isIdentifierComposite;

        return $metadata;
    }

    /**
     * @param object $object
     * @param string $propertyName
     * @param mixed  $returnValue
     */
    private function expectOriginalEntityData($object, string $propertyName, $returnValue)
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

    public function changedFieldValueDataProvider()
    {
        return [
            'changed value'         => [
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
     * @dataProvider changedFieldValueDataProvider
     */
    public function testViolationRaisedInCaseFieldValueHasChanged($oldValue, $newValue)
    {
        $object = new TestEntity();
        $fieldName = 'email';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::any())
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

    public function notChangedFieldValueDataProvider()
    {
        return [
            'not changed value'         => [
                'old value' => 'value',
                'new value' => 'value'
            ],
            'not changed value is null' => [
                'old value' => null,
                'new value' => null
            ],
            'old value was null'        => [
                'old value' => null,
                'new value' => 'value'
            ]
        ];
    }

    /**
     * @dataProvider notChangedFieldValueDataProvider
     */
    public function testViolationShouldNotBeRaisedInCaseFieldValueHasNotChanged($oldValue, $newValue)
    {
        $object = new TestEntity();
        $fieldName = 'email';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(false);

        $this->expectOriginalEntityData($object, $fieldName, $oldValue);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $fieldName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testViolationRaisedInCaseAssociationValueHasChanged()
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
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with($associationName)
            ->willReturn(TestTargetEntity::class);
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

    public function testViolationRaisedInCaseAssociationValueHasChangedToNull()
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
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with($associationName)
            ->willReturn(TestTargetEntity::class);
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

    public function testViolationShouldNotBeRaisedInCaseAssociationValueHasNotChanged()
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
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with($associationName)
            ->willReturn(TestTargetEntity::class);
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

    public function testViolationShouldNotBeRaisedInCaseAssociationValueHasNotChangedAndBothOldAndNewValuesAreNull()
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
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with($associationName)
            ->willReturn(TestTargetEntity::class);
        $targetMetadata->expects(self::never())
            ->method('getIdentifierValues');

        $this->expectOriginalEntityData($object, $associationName, null);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testViolationShouldNotBeRaisedInCaseAssociationValueWasNull()
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
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with($associationName)
            ->willReturn(TestTargetEntity::class);
        $targetMetadata->expects(self::never())
            ->method('getIdentifierValues');

        $this->expectOriginalEntityData($object, $associationName, null);

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testViolationShouldNotBeRaisedInCaseAssociationValueToBeValidatedHasInvalidType()
    {
        $newValue = 'new value';

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::never())
            ->method('getAssociationTargetClass');
        $metadata->expects(self::never())
            ->method('getIdentifierValues');

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testViolationShouldNotBeRaisedInCaseAssociationValueToBeValidatedHasInvalidTypeOfObject()
    {
        $newValue = new \stdClass();

        $object = new TestEntity();
        $associationName = 'owner';
        $metadata = $this->getClassMetadata(TestEntity::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with($associationName)
            ->willReturn(TestTargetEntity::class);
        $metadata->expects(self::never())
            ->method('getIdentifierValues');

        $constraint = new UnchangeableField();
        $this->setProperty($object, $associationName);
        $this->validator->validate($newValue, $constraint);

        $this->assertNoViolation();
    }

    public function testShouldThrowExceptionInCaseTargetEntityHasCompositeIdentifier()
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
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->with($associationName)
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with($associationName)
            ->willReturn(TestTargetEntity::class);

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
}
