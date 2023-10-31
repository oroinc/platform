<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Adaptive constraint validator for a collection
 *  - validates new elements with AdaptivelyValidCollection::$validationGroupsForNew validation groups;
 *  - validates updated elements with AdaptivelyValidCollection::$validationGroupsForUpdated
 *    validation groups;
 * - validates unchanged elements with AdaptivelyValidCollection::$validationGroupsForUnchanged
 *     validation groups.
 */
class AdaptivelyValidCollectionValidator extends ConstraintValidator
{
    private EntityStateChecker $entityStateChecker;

    public function __construct(EntityStateChecker $entityStateChecker)
    {
        $this->entityStateChecker = $entityStateChecker;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof AdaptivelyValidCollection) {
            throw new UnexpectedTypeException($constraint, AdaptivelyValidCollection::class);
        }

        if (empty($value)) {
            return;
        }

        if (!is_iterable($value)) {
            throw new UnexpectedValueException($value, 'iterable');
        }

        $validator = $this->context->getValidator()->inContext($this->context);

        $groupsForNew = ValidationGroupUtils::resolveValidationGroups($constraint->validationGroupsForNew);
        $groupsForUpdated = ValidationGroupUtils::resolveValidationGroups($constraint->validationGroupsForUpdated);
        $groupsForUnchanged = ValidationGroupUtils::resolveValidationGroups($constraint->validationGroupsForUnchanged);

        foreach ($value as $key => $entity) {
            if ($this->entityStateChecker->isNewEntity($entity)) {
                $validationGroups = $groupsForNew;
            } elseif ($constraint->trackFields) {
                if ($this->entityStateChecker->isChangedEntity($entity, $constraint->trackFields)) {
                    $validationGroups = $groupsForUpdated;
                } else {
                    $validationGroups = $groupsForUnchanged;
                }
            } else {
                $validationGroups = [];
            }

            if ($validationGroups) {
                $validator
                    ->atPath('[' . $key . ']')
                    ->validate($entity, null, $validationGroups);
            }
        }
    }
}
