<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\ValidationException;
use Oro\Bundle\EntityMergeBundle\Model\Step\ValidateStep;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidateStepTest extends \PHPUnit\Framework\TestCase
{
    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var ValidateStep */
    private $step;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->step = new ValidateStep($this->validator);
    }

    public function testRunWhenNoValidationConstraintViolations()
    {
        $data = $this->createMock(EntityData::class);

        $constraintViolations = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->identicalTo($data))
            ->willReturn($constraintViolations);
        $constraintViolations->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->step->run($data);
    }

    public function testRunWhenHasValidationConstraintViolations()
    {
        $this->expectException(ValidationException::class);

        $data = $this->createMock(EntityData::class);

        $constraintViolations = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->identicalTo($data))
            ->willReturn($constraintViolations);
        $constraintViolations->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $this->step->run($data);
    }
}
