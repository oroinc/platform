<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractor;
use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover;
use Oro\Bundle\SecurityBundle\Validator\Constraints\FieldAccessGranted;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Blank;

class ConstraintTextExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConstraintTextExtractor */
    private $constraintTextExtractor;

    protected function setUp()
    {
        $this->constraintTextExtractor = new ConstraintTextExtractor();
    }

    /**
     * @dataProvider getConstraintStatusCodeDataProvider()
     */
    public function testGetConstraintStatusCode(Constraint $constraint, $expectedStatusCode)
    {
        self::assertEquals(
            $expectedStatusCode,
            $this->constraintTextExtractor->getConstraintStatusCode($constraint)
        );
    }

    public function getConstraintStatusCodeDataProvider()
    {
        return [
            [new Blank(), 400],
            [new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'test']), 501],
            [new FieldAccessGranted(), 403]
        ];
    }

    public function testGetConstraintCode()
    {
        self::assertNull($this->constraintTextExtractor->getConstraintCode(new Blank()));
    }

    /**
     * @dataProvider getConstraintTypeDataProvider
     */
    public function testConstraintType(Constraint $constraint, $expectedType)
    {
        self::assertEquals(
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
            ]
        ];
    }
}
