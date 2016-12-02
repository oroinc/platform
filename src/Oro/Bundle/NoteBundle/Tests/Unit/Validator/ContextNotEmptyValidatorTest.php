<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Validator;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\CalendarBundle\Validator\Constraints\Recurrence;
use Oro\Bundle\CalendarBundle\Validator\RecurrenceValidator;
use Oro\Bundle\NoteBundle\Validator\Constraints\ContextNotEmpty;
use Oro\Bundle\NoteBundle\Validator\ContextNotEmptyValidator;

class ContextNotEmptyValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextNotEmpty
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entity;

    /**
     * @var ContextNotEmptyValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new ContextNotEmpty();
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->entity = $this->getMock('Oro\Bundle\NoteBundle\Entity\Note');

        $this->validator = new ContextNotEmptyValidator();
        $this->validator->initialize($this->context);
    }

    public function testNoteWithNotEmptyContextHasNoViolationAdded()
    {
        $targetEntities = [$this->getMock('TestEntity')];

        $this->entity->expects($this->once())
            ->method('getActivityTargetEntities')
            ->willReturn($targetEntities);

        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($this->entity, $this->constraint);
    }

    public function testNoteWithEmptyContextHasViolationAdded()
    {
        $targetEntities = [];

        $this->entity->expects($this->once())
            ->method('getActivityTargetEntities')
            ->willReturn($targetEntities);

        $this->expectAddViolation(
            $this->once(),
            $this->constraint->message,
            $targetEntities,
            'contexts'
        );

        $this->validator->validate($this->entity, $this->constraint);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
     * @param string $message
     * @param array $parameters
     * @param mixed $invalidValue
     * @param string|null $path
     */
    protected function expectAddViolation(
        \PHPUnit_Framework_MockObject_Matcher_Invocation $matcher,
        $message,
        $invalidValue,
        $path
    ) {
        $builder = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');

        $this->context->expects($matcher)
            ->method('buildViolation')
            ->with($message, [])
            ->will($this->returnValue($builder));

        $builder->expects($this->once())
            ->method('setInvalidValue')
            ->with($invalidValue)
            ->will($this->returnSelf());

        if ($path) {
            $builder->expects($this->once())
                ->method('atPath')
                ->with($path)
                ->will($this->returnSelf());
        }

        $builder->expects($this->once())
            ->method('addViolation');
    }
}
