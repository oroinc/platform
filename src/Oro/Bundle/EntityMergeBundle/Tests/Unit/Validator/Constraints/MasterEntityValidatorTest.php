<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MasterEntity;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\MasterEntityValidator;
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

    /**
     * @dataProvider invalidArgumentProvider
     */
    public function testInvalidArgument(mixed $value, string $expectedExceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->validator->validate($value, new MasterEntity());
    }

    public function invalidArgumentProvider(): array
    {
        return [
            'scalar'  => [
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
