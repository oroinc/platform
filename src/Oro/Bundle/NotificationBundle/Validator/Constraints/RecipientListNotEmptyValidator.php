<?php

namespace Oro\Bundle\NotificationBundle\Validator\Constraints;

use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for checking if recipient list is not empty.
 */
class RecipientListNotEmptyValidator extends ConstraintValidator
{
    /**
     * @param RecipientList $entity
     * @param RecipientListNotEmpty $constraint
     */
    public function validate($entity, Constraint $constraint): void
    {
        if (!is_a($entity, RecipientList::class)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value was expected to be an instance of %s, %s got given',
                    RecipientList::class,
                    get_debug_type($entity)
                )
            );
        }

        $notValid =
            $entity->getGroups()->isEmpty()
            && $entity->getUsers()->isEmpty()
            && !$entity->getEmail()
            && !$entity->getAdditionalEmailAssociations()
            && !$entity->getEntityEmails();

        if ($notValid) {
            $this->context
                ->addViolation($constraint->message);
        }
    }
}
