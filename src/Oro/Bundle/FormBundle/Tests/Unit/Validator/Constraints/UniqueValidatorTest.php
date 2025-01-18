<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FormBundle\Validator\Constraints\Unique;
use Oro\Bundle\FormBundle\Validator\Constraints\UniqueValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraints\Unique as SymfonyUniqueConstraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class UniqueValidatorTest extends ConstraintValidatorTestCase
{
    private PropertyAccessorInterface&MockObject $propertyAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): UniqueValidator
    {
        return new UniqueValidator($this->propertyAccessor);
    }

    public function testValidateWithInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate([], $this->createMock(SymfonyUniqueConstraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $constraint = new Unique(['fields' => []]);
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithNonIterableValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $constraint = new Unique(['fields' => []]);
        $this->validator->validate('non-iterable', $constraint);
    }

    public function testValidateWithUniqueValues(): void
    {
        $constraint = new Unique(['fields' => []]);
        $this->validator->validate([1, 2, 3], $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithDuplicateValues(): void
    {
        $constraint = new Unique([
            'fields' => [],
            'message' => 'Duplicate value found.'
        ]);
        $this->validator->validate([1, 2, 2], $constraint);

        $this->buildViolation('Duplicate value found.')
            ->setParameter('{{ value }}', 'array')
            ->setCode(SymfonyUniqueConstraint::IS_NOT_UNIQUE)
            ->assertRaised();
    }

    public function testValidateWithFieldsAndReadableProperties(): void
    {
        $object1 = (object)['field1' => 'value1', 'field2' => 'value2'];
        $object2 = (object)['field1' => 'value1', 'field2' => 'value3'];
        $object3 = (object)['field1' => 'value1', 'field2' => 'value2'];

        $this->propertyAccessor->expects(self::atLeastOnce())
            ->method('isReadable')
            ->willReturn(true);
        $this->propertyAccessor->expects(self::atLeastOnce())
            ->method('getValue')
            ->willReturnMap([
                [$object1, 'field1', 'value1'],
                [$object1, 'field2', 'value2'],
                [$object2, 'field1', 'value1'],
                [$object2, 'field2', 'value3'],
                [$object3, 'field1', 'value1'],
                [$object3, 'field2', 'value2']
            ]);

        $constraint = new Unique(['fields' => ['field1', 'field2']]);
        $this->validator->validate([$object1, $object2, $object3], $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'array')
            ->setCode(SymfonyUniqueConstraint::IS_NOT_UNIQUE)
            ->assertRaised();
    }

    public function testValidateWithNormalizer(): void
    {
        $constraint = new Unique([
            'fields' => [],
            'normalizer' => static fn ($value) => strtolower($value),
            'message' => 'Duplicate value found.',
        ]);
        $this->validator->validate(['Value', 'VALUE'], $constraint);

        $this->buildViolation('Duplicate value found.')
            ->setParameter('{{ value }}', 'array')
            ->setCode(SymfonyUniqueConstraint::IS_NOT_UNIQUE)
            ->assertRaised();
    }

    public function testReduceElementKeysWithUnreadableProperty(): void
    {
        $object = (object)['field1' => 'value1'];

        $this->propertyAccessor->expects(self::once())
            ->method('isReadable')
            ->willReturn(false);

        $constraint = new Unique(['fields' => ['field1']]);
        $this->validator->validate([$object], $constraint);

        $this->assertNoViolation();
    }
}
