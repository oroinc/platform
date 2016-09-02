<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Blank;

use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractor;
use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover;

class ConstraintTextExtractorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConstraintTextExtractor */
    protected $constraintTextExtractor;

    protected function setUp()
    {
        $this->constraintTextExtractor = new ConstraintTextExtractor();
    }

    /**
     * @dataProvider getConstraintStatusCodeDataProvider()
     */
    public function testGetConstraintStatusCode(Constraint $constraint, $expectedStatusCode)
    {
        $this->assertEquals(
            $expectedStatusCode,
            $this->constraintTextExtractor->getConstraintStatusCode($constraint)
        );
    }

    public function getConstraintStatusCodeDataProvider()
    {
        return [
            [new Blank(), 400],
            [new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'test']), 501],
        ];
    }

    public function testGetConstraintCode()
    {
        $this->assertNull($this->constraintTextExtractor->getConstraintCode(new Blank()));
    }

    /**
     * @dataProvider getConstraintTypeDataProvider
     */
    public function testConstraintType(Constraint $constraint, $expectedType)
    {
        $this->assertEquals(
            $expectedType,
            $this->constraintTextExtractor->getConstraintType($constraint)
        );
    }

    public function getConstraintTypeDataProvider()
    {
        return [
            [new Blank(), 'blank constraint'],
            [
                new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'test']),
                'has adder and remover constraint'
            ],
        ];
    }
}
