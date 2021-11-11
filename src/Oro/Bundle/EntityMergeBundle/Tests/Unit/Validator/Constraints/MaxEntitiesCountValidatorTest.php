<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MaxEntitiesCount;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MaxEntitiesCountValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MaxEntitiesCountValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new MaxEntitiesCountValidator();
    }

    /**
     * @dataProvider invalidArgumentProvider
     */
    public function testInvalidArgument(mixed $value, string $expectedExceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->validator->validate($value, new MaxEntitiesCount());
    }

    public function invalidArgumentProvider(): array
    {
        return [
            'bool'    => [
                'value'                    => true,
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, boolean given'
            ],
            'string'  => [
                'value'                    => 'string',
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, string given'
            ],
            'integer' => [
                'value'                    => 5,
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, integer given'
            ],
            'null'    => [
                'value'                    => null,
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, NULL given'
            ],
            'object'  => [
                'value'                    => new \stdClass(),
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, stdClass given'
            ],
            'array'   => [
                'value'                    => [],
                'expectedExceptionMessage' =>
                    'Oro\Bundle\EntityMergeBundle\Data\EntityData supported only, array given'
            ],
        ];
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
