<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\SourceEntity;
use Oro\Bundle\EntityMergeBundle\Validator\Constraints\SourceEntityValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SourceEntityValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('isEntityEqual')
            ->willReturnCallback(function ($entity, $other) {
                return $entity->getId() === $other->getId();
            });

        return new SourceEntityValidator($doctrineHelper);
    }

    private function createFieldData($sourceEntity)
    {
        $fieldData = $this->createMock(FieldData::class);
        $fieldData->expects($this->any())
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);
        if ($sourceEntity) {
            $fieldData->expects($this->any())
                ->method('getFieldName')
                ->willReturn('field-' . $sourceEntity->getId());
        }

        return $fieldData;
    }

    /**
     * @dataProvider invalidArgumentProvider
     */
    public function testInvalidArgument(mixed $value, string $expectedExceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $constraint = new SourceEntity();
        $this->validator->validate($value, $constraint);
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

    public function testValid()
    {
        $sourceEntity = new EntityStub('entity-0');

        $entityData = $this->createMock(EntityData::class);
        $entityData->expects($this->any())
            ->method('getEntities')
            ->willReturn([new EntityStub('entity-0'), new EntityStub('entity-1')]);
        $entityData->expects($this->any())
            ->method('getFields')
            ->willReturn([$this->createFieldData($sourceEntity)]);

        $constraint = new SourceEntity();
        $this->validator->validate($entityData, $constraint);

        $this->assertNoViolation();
    }

    public function testNonValidType()
    {
        $sourceEntity = null;

        $entityData = $this->createMock(EntityData::class);
        $entityData->expects($this->any())
            ->method('getEntities')
            ->willReturn([new EntityStub('entity-0'), new EntityStub('entity-1')]);
        $entityData->expects($this->any())
            ->method('getFields')
            ->willReturn([$this->createFieldData($sourceEntity)]);

        $constraint = new SourceEntity();
        $this->validator->validate($entityData, $constraint);

        $this->assertNoViolation();
    }

    public function testNomValid()
    {
        $sourceEntity = new EntityStub('non-valid');

        $entityData = $this->createMock(EntityData::class);
        $entityData->expects($this->any())
            ->method('getEntities')
            ->willReturn([new EntityStub('entity-0'), new EntityStub('entity-1')]);
        $entityData->expects($this->any())
            ->method('getFields')
            ->willReturn([$this->createFieldData($sourceEntity)]);

        $constraint = new SourceEntity();
        $this->validator->validate($entityData, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ limit }}', 'field-non-valid')
            ->assertRaised();
    }
}
