<?php

namespace Oro\Bundle\NoteBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Validator\Constraints\ContextNotEmpty;

class ContextNotEmptyValidator extends ConstraintValidator
{
    const VIOLATION_PATH = 'contexts';

    /**
     * Checks if the passed note has at least one relation with entity through context field.
     *
     * @param mixed|Note                 $value      The value that should be validated
     * @param Constraint|ContextNotEmpty $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        $this->assertArgumentsHaveExpectedType($value, $constraint);

        $activityTargetEntities = $value->getActivityTargetEntities();
        if (!count($activityTargetEntities)) {
            $this->addViolation($constraint->message, $activityTargetEntities);
        }
    }

    /**
     * Verifies passed arguments have expected type.
     *
     * @param mixed      $value
     * @param Constraint $constraint
     * @throws \InvalidArgumentException
     */
    protected function assertArgumentsHaveExpectedType($value, Constraint $constraint)
    {
        if (!$value instanceof Note) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value should be an instanceof "%s", "%s" given.',
                    Note::class,
                    $this->getType($value)
                )
            );
        }

        if (!$constraint instanceof ContextNotEmpty) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Constraint should be an instanceof "%s", "%s" given.',
                    ContextNotEmpty::class,
                    $this->getType($constraint)
                )
            );
        }
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function getType($value)
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }

    /**
     * @param string     $message
     * @param mixed      $invalidValue
     */
    protected function addViolation($message, $invalidValue = null)
    {
        $violationBuilder = $this->getViolationBuilder($message);
        $violationBuilder
            ->setInvalidValue($invalidValue)
            ->atPath(self::VIOLATION_PATH)
            ->addViolation();
    }

    /**
     * @param string $message
     *
     * @return ConstraintViolationBuilderInterface
     */
    protected function getViolationBuilder($message)
    {
        if ($this->context instanceof ExecutionContextInterface) {
            return $this->context->buildViolation($message);
        } else {
            return $this->buildViolation($message);
        }
    }
}
