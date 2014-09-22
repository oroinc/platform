<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmbeddedFormBundle\Validator\Constraints\NoTagsValidator;

class NoTagsValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var NoTagsValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = $this->getMock('Oro\\Bundle\\EmbeddedFormBundle\\Validator\\Constraints\\NoTags');
        $this->context = $this->getMock('Symfony\\Component\\Validator\\ExecutionContextInterface');
        $this->validator = new NoTagsValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @test
     * @dataProvider valuesWithoutErrors
     */
    public function shouldValidateWithoutErrors($value)
    {
        $this->context->expects($this->never())
            ->method($this->anything());
        $this->validator->validate($value, $this->constraint);
    }

    public function valuesWithoutErrors()
    {
        return [
            'empty value' => [''],
            'null value' => [null],
            'value with spaces' => ['   '],
            'simple css' => [
                'div.test { color: #fefefe; }'
            ],
            'css with > selector' => [
                'div.test > div.test { color: #fefefe; }'
            ],
            'css with > content' => [
                'div.test:before { content: \'>\'; }'
            ],
            'css with < content' => [
                'div.test:before { content: \'< \'; }'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider valuesWithErrors
     */
    public function shouldValidateWithErrors($value)
    {
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->message);

        $this->validator->validate($value, $this->constraint);
    }

    public function valuesWithErrors()
    {
        return [
            'value with closed style' => [
                'div.test { color: #fefefe; } </style>'
            ],
            'value with tags' => [
                'div.test { color: #fefefe; } <b>test</b>'
            ],
            'value with < in content' => [
                'div.test:before { content: \'<\'; }'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidValues
     */
    public function shouldFailWithInvalidValue($value, $exceptionMessage)
    {
        $this->setExpectedException(
            'Symfony\\Component\\Validator\\Exception\\UnexpectedTypeException',
            $exceptionMessage
        );
        $this->validator->validate($value, $this->constraint);
    }

    public function invalidValues()
    {
        return [
            'array' => [
                [1],
                'Expected argument of type "string", "array" given'
            ],
            'object' => [
                new \stdClass(),
                'Expected argument of type "string", "stdClass" given'
            ],
        ];
    }
}
