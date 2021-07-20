<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FormBundle\Validator\Constraints\HtmlNotBlank;
use Oro\Bundle\FormBundle\Validator\Constraints\HtmlNotBlankValidator;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class HtmlNotBlankValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider validItemsDataProvider
     * @param string $value
     */
    public function testValidateValid($value): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContext $context */
        $context = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->never())
            ->method('addViolation');

        $constraint = new HtmlNotBlank();
        $validator = new HtmlNotBlankValidator();
        $validator->initialize($context);

        $validator->validate($value, $constraint);
    }

    public function validItemsDataProvider(): array
    {
        return [
            'html' => ['<p>some content</p>'],
            'image' => ['<p><img src="/"/></p>'],
            'text' => ['some content'],
        ];
    }

    /**
     * @dataProvider invalidItemsDataProvider
     * @param mixed $value
     */
    public function testValidateInvalid($value): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContext $context */
        $context = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraint = new HtmlNotBlank();
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('setCode')
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViolation');
        $validator = new HtmlNotBlankValidator();
        $validator->initialize($context);

        $validator->validate($value, $constraint);
    }

    public function invalidItemsDataProvider(): array
    {
        return [
            'empty string' => [''],
            'one white-space' => [' '],
            'few white-spaces' => ['     '],
            'false' => [false],
            'null' => [null],
            'empty html' => ['<p></p>'],
            'empty html with attr' => ['<p class="empty"></p>'],
        ];
    }
}
