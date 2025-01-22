<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FormBundle\Validator\Constraints\UniqueCollectionItem;
use Oro\Bundle\FormBundle\Validator\Constraints\UniqueCollectionItemValidator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class UniqueCollectionItemValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): UniqueCollectionItemValidator
    {
        return new UniqueCollectionItemValidator(PropertyAccess::createPropertyAccessor());
    }

    public function testValidateWithInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate([], $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $constraint = new UniqueCollectionItem(['collection' => 'items', 'fields' => ['field1']]);
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithNonObjectValue(): void
    {
        $constraint = new UniqueCollectionItem(['collection' => 'items', 'fields' => ['field1']]);
        $this->validator->validate('non-object', $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenValueIsUnique(): void
    {
        $value = (object)[
            'field1' => 'value1',
            'field2' => 'value2',
            'items' => [
                (object)['field1' => 'value2', 'field2' => 'value2'],
                (object)['field1' => 'value3', 'field2' => 'value2']
            ]
        ];

        $constraint = new UniqueCollectionItem(['collection' => 'items', 'fields' => ['field1']]);
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenValueIsDuplicate(): void
    {
        $value = (object)[
            'field1' => 'value1',
            'field2' => 'value2',
            'items' => [
                (object)['field1' => 'value2', 'field2' => 'value2'],
                (object)['field1' => 'value1', 'field2' => 'value2']
            ]
        ];

        $constraint = new UniqueCollectionItem(['collection' => 'items', 'fields' => ['field1']]);
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateWithFieldsAndReadableProperties(): void
    {
        $value = (object)[
            'field1' => 'value1',
            'field2' => 'value2',
            'items' => [
                (object)['field1' => 'value1', 'field2' => 'value2'],
                (object)['field1' => 'value1', 'field2' => 'value3'],
                (object)['field1' => 'value1', 'field2' => 'value2']
            ]
        ];

        $constraint = new UniqueCollectionItem(['collection' => 'items', 'fields' => ['field1', 'field2']]);
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testReduceElementKeysWithUnreadableCollectionProperty(): void
    {
        $value = (object)[
            'field1' => 'value1',
            'field2' => 'value2'
        ];

        $constraint = new UniqueCollectionItem(['collection' => 'items', 'fields' => ['field1']]);
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testReduceElementKeysWithUnreadableValueProperty(): void
    {
        $value = (object)[
            'field2' => 'value2',
            'items' => [
                (object)['field1' => 'value1']
            ]
        ];

        $constraint = new UniqueCollectionItem(['collection' => 'items', 'fields' => ['field1']]);
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testReduceElementKeysWithUnreadableProperty(): void
    {
        $value = (object)[
            'field1' => 'value1',
            'field2' => 'value2',
            'items' => [
                (object)['field1' => 'value1']
            ]
        ];

        $constraint = new UniqueCollectionItem(['collection' => 'items', 'fields' => ['field2']]);
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
