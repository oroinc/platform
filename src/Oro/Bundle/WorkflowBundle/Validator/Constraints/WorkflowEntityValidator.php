<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates if entity can be changed taking into account workflow.
 */
class WorkflowEntityValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly WorkflowPermissionRegistry $permissionRegistry,
        private readonly RestrictionManager $restrictionManager,
        private readonly FieldHelper $fieldHelper,
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof WorkflowEntity) {
            throw new UnexpectedTypeException($constraint, WorkflowEntity::class);
        }

        if (!\is_object($value)) {
            return;
        }

        // skip changes for workflow transition form
        $root = $this->context->getRoot();
        if ($root instanceof FormInterface && WorkflowTransitionType::NAME === $root->getName()) {
            return;
        }

        $class = $this->doctrineHelper->getEntityClass($value);
        $hasClassRestrictions = $this->restrictionManager->hasEntityClassRestrictions($class);
        if (!$hasClassRestrictions && !$this->permissionRegistry->supportsClass($class)) {
            return;
        }

        $restrictions = $this->restrictionManager->getEntityRestrictions($value);
        if ($this->doctrineHelper->isNewEntity($value)) {
            $this->validateNewEntity($value, $constraint, $restrictions);
        } else {
            $this->validateExistingEntity($value, $constraint, $restrictions);
        }
    }

    private function validateNewEntity(object $object, WorkflowEntity $constraint, array $restrictions): void
    {
        foreach ($restrictions as $restriction) {
            if ('full' === $restriction['mode']) {
                $fieldValue = $this->propertyAccessor->getValue($object, $restriction['field']);
                if ($fieldValue === null || ($fieldValue instanceof EmptyItem && $fieldValue->isEmpty())) {
                    continue;
                }
                $this->addFieldViolation($restriction['field'], $constraint->createFieldMessage);
            } else {
                $this->validateAllowedValues($object, $constraint->createFieldMessage, $restriction);
            }
        }
    }

    private function validateExistingEntity(object $object, WorkflowEntity $constraint, array $restrictions): void
    {
        $permissions = $this->permissionRegistry->getEntityPermissions($object);
        if (true === $permissions['UPDATE'] && empty($restrictions)) {
            return;
        }

        if (false === $permissions['UPDATE']) {
            $this->context->addViolation($constraint->updateEntityMessage);

            return;
        }

        $changeSet = $this->getEntityChangeSet($object);
        if (!$changeSet) {
            return;
        }

        foreach ($restrictions as $restriction) {
            if (!isset($changeSet[$restriction['field']])) {
                continue;
            }
            if ('full' === $restriction['mode']) {
                $this->addFieldViolation($restriction['field'], $constraint->updateFieldMessage);
            } else {
                $this->validateAllowedValues($object, $constraint->updateFieldMessage, $restriction);
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getEntityChangeSet(object $object): array
    {
        $changesSet = [];
        $class =  $this->doctrineHelper->getEntityClass($object);
        $originalData = $this->doctrineHelper->getEntityManagerForClass($class)
            ->getUnitOfWork()
            ->getOriginalEntityData($object);
        $fieldList = $this->fieldHelper->getEntityFields($class, EntityFieldProvider::OPTION_WITH_RELATIONS);
        foreach ($fieldList as $field) {
            $fieldName = $field['name'];
            $isEnumerableType = ExtendHelper::isEnumerableType($field['type']);
            // skip field, its a partially omitted one
            if (
                !(isset($originalData[$fieldName]) || \array_key_exists($fieldName, $originalData))
                && !($isEnumerableType && isset($originalData['serialized_data'][$fieldName]))
            ) {
                continue;
            }

            $actualValue = $this->fieldHelper->getObjectValue($object, $fieldName);
            $originalValue = $isEnumerableType
                ? $originalData['serialized_data'][$fieldName]
                : $originalData[$fieldName];
            if ($isEnumerableType) {
                $actualValue = ExtendHelper::isSingleEnumType($field['type'])
                    ? $actualValue?->getId()
                    : array_map(fn ($option) => $option?->getId(), $actualValue);
            }
            if ($actualValue !== $originalValue) {
                $changesSet[$fieldName] = [$originalValue, $actualValue];
            }
        }

        return $changesSet;
    }

    private function validateAllowedValues(object $object, string $message, array $restriction): void
    {
        $fieldValue = $this->propertyAccessor->getValue($object, $restriction['field']);
        if (\is_object($fieldValue)) {
            $fieldValue = $this->doctrineHelper->getSingleEntityIdentifier($fieldValue);
        }

        if ('allow' === $restriction['mode']) {
            if (!\in_array($fieldValue, $restriction['values'], true)) {
                $this->addFieldViolation($restriction['field'], $message);
            }
        } elseif (\in_array($fieldValue, $restriction['values'], true)) {
            $this->addFieldViolation($restriction['field'], $message);
        }
    }

    private function addFieldViolation(string $field, string $message): void
    {
        $this->context->buildViolation($message)
            ->atPath($field)
            ->addViolation();
    }
}
