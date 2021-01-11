<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\NoColumnDuplicates;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\NoColumnDuplicatesValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NoColumnDuplicatesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NoColumnDuplicatesValidator
    {
        return new NoColumnDuplicatesValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new QueryDesigner(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new NoColumnDuplicates());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new NoColumnDuplicates());
    }

    public function testInvalidJsonShouldBeIgnored(): void
    {
        $value = new QueryDesigner('Test\Entity', 'invalid json');
        $this->validator->validate($value, new NoColumnDuplicates());
        $this->assertNoViolation();
    }

    public function testDefinitionWithDuplicateColumns(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'test', 'func' => '', 'label' => 'Test'],
                    ['name' => 'another', 'func' => '', 'label' => 'Test'],
                    ['name' => 'test', 'func' => '', 'label' => 'Test'],
                    ['name' => 'test', 'func' => ['name' => 'testFunc'], 'label' => 'Test (testFunc)'],
                    ['name' => 'test', 'func' => ['name' => 'testFunc1'], 'label' => 'Test (testFunc1)'],
                    ['name' => 'test', 'func' => ['name' => 'testFunc'], 'label' => 'Test (testFunc)'],
                    ['name' => 'test', 'func' => ['name' => 'testFunc'], 'label' => 'Test (testFunc)']
                ]
            ])
        );

        $constraint = new NoColumnDuplicates();
        $this->validator->validate($value, new NoColumnDuplicates());
        $this->buildViolation($constraint->message)
            ->setParameter('%duplicates%', 'Test, Test (testFunc), Test (testFunc)')
            ->assertRaised();
    }

    public function testDefinitionWithoutDuplicateColumns(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'test', 'func' => ['name' => 'testFunc'], 'label' => 'Test (testFunc)'],
                    ['name' => 'test', 'func' => '', 'label' => 'Test'],
                    ['name' => 'test', 'func' => ['name' => 'testFunc1'], 'label' => 'Test (testFunc1)'],
                    [
                        'name'  => 'test',
                        'func'  => ['name' => 'testFunc', 'group_name' => 'group1'],
                        'label' => 'Test (testFunc,group1)'
                    ],
                    [
                        'name'  => 'test',
                        'func'  => ['name' => 'testFunc', 'group_name' => 'group2'],
                        'label' => 'Test (testFunc,group2)'
                    ]
                ]
            ])
        );

        $this->validator->validate($value, new NoColumnDuplicates());
        $this->assertNoViolation();
    }
}
