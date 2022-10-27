<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MaxEntitiesCount;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MaxEntitiesCountValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MaxEntitiesCountValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new MaxEntitiesCountValidator();
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(EntityData::class), $this->createMock(Constraint::class));
    }

    public function testValueIsNotEntityData()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new MaxEntitiesCount());
    }

    public function testValidateForValidValueAndDefaultMaxEntitiesCount()
    {
        $value = $this->createEntityData(5, 2);

        $constraint = new MaxEntitiesCount();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForValidValueAndCustomMaxEntitiesCount()
    {
        $value = $this->createEntityData(10, 2);

        $constraint = new MaxEntitiesCount();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForInvalidValue()
    {
        $value = $this->createEntityData(5, 10);

        $constraint = new MaxEntitiesCount();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ limit }}', 5)
            ->assertRaised();
    }

    private function createEntityData(int $maxCount, int $count): EntityData
    {
        $entityData = $this->createMock(EntityData::class);
        $metadata = $this->createMock(EntityMetadata::class);

        $metadata->expects($this->any())
            ->method('getMaxEntitiesCount')
            ->willReturn($maxCount);

        $entityData->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $entityData->expects($this->any())
            ->method('getEntities')
            ->willReturn(array_fill(0, $count, new \stdClass()));

        return $entityData;
    }
}
