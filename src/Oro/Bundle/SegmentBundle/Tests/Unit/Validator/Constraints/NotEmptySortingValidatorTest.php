<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Validator\Constraints\NotEmptySorting;
use Oro\Bundle\SegmentBundle\Validator\Constraints\NotEmptySortingValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotEmptySortingValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotEmptySortingValidator
    {
        return new NotEmptySortingValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new Segment(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new NotEmptySorting());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new NotEmptySorting());
    }

    public function testInvalidJsonShouldBeIgnored(): void
    {
        $segment = new Segment();
        $segment->setDefinition('invalid json');
        $this->validator->validate($segment, new NotEmptySorting());
        $this->assertNoViolation();
    }

    public function testWithoutRecordsLimit(): void
    {
        $segment = new Segment();
        $segment->setDefinition(QueryDefinitionUtil::encodeDefinition([
            'columns' => [
                ['name' => 'field', 'label' => 'Field', 'sorting' => '', 'func' => '']
            ]
        ]));

        $this->validator->validate($segment, new NotEmptySorting());
        $this->assertNoViolation();
    }

    public function testWitZeroRecordsLimit(): void
    {
        $segment = new Segment();
        $segment->setRecordsLimit(0);
        $segment->setDefinition(QueryDefinitionUtil::encodeDefinition([
            'columns' => [
                ['name' => 'field', 'label' => 'Field', 'sorting' => '', 'func' => '']
            ]
        ]));

        $this->validator->validate($segment, new NotEmptySorting());
        $this->assertNoViolation();
    }

    public function testWithRecordsLimitAndWithEmptyDefinition(): void
    {
        $segment = new Segment();
        $segment->setRecordsLimit(10);

        $this->validator->validate($segment, new NotEmptySorting());
        $this->assertNoViolation();
    }

    public function testWithRecordsLimitAndWithoutSortedColumns(): void
    {
        $segment = new Segment();
        $segment->setRecordsLimit(10);
        $segment->setDefinition(QueryDefinitionUtil::encodeDefinition([
            'columns' => [
                ['name' => 'field', 'label' => 'Field', 'sorting' => '', 'func' => '']
            ]
        ]));

        $constraint = new NotEmptySorting();
        $this->validator->validate($segment, $constraint);
        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testWithRecordsLimitAndWithSortedColumns(): void
    {
        $segment = new Segment();
        $segment->setRecordsLimit(10);
        $segment->setDefinition(QueryDefinitionUtil::encodeDefinition([
            'columns' => [
                ['name' => 'field1', 'label' => 'Field1', 'sorting' => 'asc', 'func' => ''],
                ['name' => 'field2', 'label' => 'Field2', 'sorting' => '', 'func' => '']
            ]
        ]));

        $this->validator->validate($segment, new NotEmptySorting());
        $this->assertNoViolation();
    }
}
