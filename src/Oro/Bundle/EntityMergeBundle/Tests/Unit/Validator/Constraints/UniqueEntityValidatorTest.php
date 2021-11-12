<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\UniqueEntityValidator;
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

    /**
     * @dataProvider invalidArgumentProvider
     */
    public function testInvalidArgument(mixed $value, string $expectedExceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->validator->validate($value, new UniqueEntity());
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
