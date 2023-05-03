<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverter;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Type;

class ConstraintConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConstraintFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $constraintFactory;

    /** @var ConstraintConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->constraintFactory = $this->createMock(ConstraintFactory::class);
        $this->converter = new ConstraintConverter($this->constraintFactory);
    }

    public function testConvertConstraintWhenNoJsValidation(): void
    {
        $constraint = new Type(['type' => 'string']);

        $this->constraintFactory->expects(self::never())
            ->method('create');

        self::assertSame($constraint, $this->converter->convertConstraint($constraint));
    }

    public function testConvertConstraintWhenHasJsValidation(): void
    {
        $options = ['sample_key' => 'sample_value'];
        $constraintName = 'DateTime';
        $constraint = new Type(
            \DateTimeInterface::class,
            null,
            null,
            ['jsValidation' => ['type' => $constraintName, 'options' => $options]]
        );

        $newConstraint = $this->createMock(Constraint::class);
        $this->constraintFactory->expects(self::once())
            ->method('create')
            ->with($constraintName, $options)
            ->willReturn($newConstraint);

        self::assertSame($newConstraint, $this->converter->convertConstraint($constraint));
    }
}
