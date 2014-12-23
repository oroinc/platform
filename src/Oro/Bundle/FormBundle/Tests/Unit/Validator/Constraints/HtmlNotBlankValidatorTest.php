<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\ExecutionContext;

use Oro\Bundle\FormBundle\Validator\Constraints\HtmlNotBlank;
use Oro\Bundle\FormBundle\Validator\Constraints\HtmlNotBlankValidator;

class HtmlNotBlankValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider validItemsDataProvider
     * @param string $value
     */
    public function testValidateValid($value)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context */
        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->never())
            ->method('addViolation');

        $constraint = new HtmlNotBlank();
        $validator = new HtmlNotBlankValidator();
        $validator->initialize($context);

        $validator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function validItemsDataProvider()
    {
        return [
            'html' => ['<p>some content</p>'],
            'text' => ['some content'],
        ];
    }

    /**
     * @dataProvider invalidItemsDataProvider
     * @param mixed $value
     */
    public function testValidateInvalid($value)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context */
        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();

        $constraint = new HtmlNotBlank();
        $context->expects($this->once())
            ->method('addViolation')
            ->with($constraint->message);

        $validator = new HtmlNotBlankValidator();
        $validator->initialize($context);

        $validator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function invalidItemsDataProvider()
    {
        return array(
            'empty string' => [''],
            'false' => [false],
            'null' => [null],
            'empty html' => ['<p></p>'],
            'empty html with attr' => ['<p class="empty"></p>'],
        );
    }
}
