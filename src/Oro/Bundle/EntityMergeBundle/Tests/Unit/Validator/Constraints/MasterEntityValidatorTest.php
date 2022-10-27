<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MasterEntity;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MasterEntityValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MasterEntityValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('isEntityEqual')
            ->willReturnCallback(function ($entity, $other) {
                return $entity->getId() === $other->getId();
            });

        return new MasterEntityValidator($doctrineHelper);
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(EntityData::class), $this->createMock(Constraint::class));
    }

    public function testValueIsNotEntityData()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new MasterEntity());
    }

    public function testValidateForValidValue()
    {
        $masterEntity = new EntityStub('entity');

        $value = $this->createMock(EntityData::class);
        $value->expects($this->any())
            ->method('getEntities')
            ->willReturn([new EntityStub('entity'), new EntityStub('entity-2')]);
        $value->expects($this->any())
            ->method('getMasterEntity')
            ->willReturn($masterEntity);

        $constraint = new MasterEntity();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForInvalidValue()
    {
        $masterEntity = new EntityStub('non-valid');

        $value = $this->createMock(EntityData::class);
        $value->expects($this->any())
            ->method('getEntities')
            ->willReturn([new EntityStub('entity'), new EntityStub('entity-2')]);
        $value->expects($this->any())
            ->method('getMasterEntity')
            ->willReturn($masterEntity);

        $constraint = new MasterEntity();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
