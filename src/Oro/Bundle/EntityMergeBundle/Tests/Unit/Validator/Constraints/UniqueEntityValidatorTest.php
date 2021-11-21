<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueEntityValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityIdentifierValue')
            ->willReturnCallback(function ($entity) {
                return $entity->getId();
            });

        return new UniqueEntityValidator($doctrineHelper);
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(EntityData::class), $this->createMock(Constraint::class));
    }

    public function testValueIsNotEntityData()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new UniqueEntity());
    }

    public function testValidateForValidValue()
    {
        $value = $this->createEntityData(['entity-0', 'entity-1']);

        $constraint = new UniqueEntity();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForInvalidValue()
    {
        $value = $this->createEntityData(['duplicate', 'duplicate']);

        $constraint = new UniqueEntity();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    private function createEntityData(array $ids): EntityData
    {
        $entityData = $this->createMock(EntityData::class);
        $entityData->expects($this->any())
            ->method('getEntities')
            ->willReturn(array_map(
                function ($id) {
                    return new EntityStub($id);
                },
                $ids
            ));

        return $entityData;
    }
}
