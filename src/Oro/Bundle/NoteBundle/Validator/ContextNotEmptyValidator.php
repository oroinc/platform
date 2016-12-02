<?php

namespace Oro\Bundle\NoteBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Validator\Constraints\ContextNotEmpty;

class ContextNotEmptyValidator extends ConstraintValidator
{
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
            $this->addViolation(
                $constraint->message,
                [],
                $activityTargetEntities,
                'contexts'
            );
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
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if (!$constraint instanceof ContextNotEmpty) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Constraint should be an instanceof "%s", "%s" given.',
                    ContextNotEmpty::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }
    }

    /**
     * @param string $message
     * @param array $parameters
     * @param string|null $invalidValue
     * @param string|null $path
     */
    protected function addViolation($message, array $parameters = [], $invalidValue = null, $path = null)
    {
        if ($this->context instanceof ExecutionContextInterface) {
            $violationBuilder = $this->context->buildViolation($message)
                ->setParameters($parameters)
                ->setInvalidValue($invalidValue);
            if ($path) {
                $violationBuilder->atPath($path);
            }
            $violationBuilder->addViolation();
        } else {
            /** @var  $violationBuilder */
            $violationBuilder = $this->buildViolation($message)
                ->setInvalidValue($invalidValue)
                ->setParameters($parameters);
            if ($path) {
                $violationBuilder->atPath($path);
            }
            $violationBuilder->addViolation();
        }
    }
}
