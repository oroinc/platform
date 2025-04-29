<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator for UnchangeableField constraint.
 */
class UnchangeableFieldValidator extends ConstraintValidator
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UnchangeableField) {
            throw new UnexpectedTypeException($constraint, UnchangeableField::class);
        }

        $entityClass = $this->context->getClassName();
        $em = $this->doctrineHelper->getEntityManagerForClass($entityClass);
        $metadata = $em->getClassMetadata($entityClass);

        $fieldName = $this->context->getPropertyName();
        if ($metadata->hasAssociation($fieldName)) {
            if ((null === $value || is_object($value))
                && $this->isAssociationValueChanged(
                    $value,
                    $this->context->getObject(),
                    $fieldName,
                    $em,
                    $metadata,
                    $constraint->allowReset,
                    $constraint->allowChangeOwner
                )
            ) {
                $this->context->addViolation($constraint->message);
            }
        } elseif ($this->isFieldValueChanged(
            $value,
            $this->context->getObject(),
            $fieldName,
            $em,
            $constraint->allowReset
        )) {
            $this->context->addViolation($constraint->message);
        }
    }

    private function isFieldValueChanged(
        mixed $value,
        object $entity,
        string $fieldName,
        EntityManagerInterface $em,
        bool $allowReset
    ): bool {
        $existingValue = $this->getExistingValue($entity, $fieldName, $em);
        if (null === $existingValue) {
            return false;
        }

        if ($allowReset && null === $value) {
            return false;
        }

        return $existingValue !== $value;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function isAssociationValueChanged(
        ?object $value,
        object $entity,
        string $fieldName,
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        bool $allowReset,
        bool $allowChangeOwner
    ): bool {
        $associationMapping = $metadata->getAssociationMapping($fieldName);
        $associationType = $associationMapping['type'];
        if ($value instanceof Collection
            && $associationType & ClassMetadata::TO_MANY
            && null !== $this->getSingleIdentifierValue($entity, $metadata)
        ) {
            return count($value->getDeleteDiff()) || count($value->getInsertDiff());
        }

        $targetEntityClass = $associationMapping['targetEntity'];
        if (is_object($value) && (!is_a($value, $targetEntityClass) && !($value instanceof PersistentCollection))) {
            return false;
        }

        $targetMetadata = $em->getClassMetadata($targetEntityClass);
        if ($targetMetadata->isIdentifierComposite) {
            throw new \LogicException(sprintf(
                '%s is not allowed to be used for %s::%s'
                . ' because the target entity %s has composite identifier.',
                self::class,
                $metadata->name,
                $fieldName,
                $targetMetadata->name
            ));
        }

        $existingValue = $this->getExistingValue($entity, $fieldName, $em);

        if (!$allowChangeOwner
            && $this->isOwnerChanged(
                $value,
                $existingValue,
                $targetMetadata,
                $associationType & ClassMetadata::ONE_TO_ONE
            )
        ) {
            return true;
        }

        if ($allowReset && null === $value) {
            return false;
        }

        return $this->isValueChanged($value, $existingValue, $targetMetadata);
    }

    private function isOwnerChanged(
        ?object $value,
        ?object $existingValue,
        ClassMetadata $metadata,
        bool $isSingleValuedInverseSideAssociation
    ): bool {
        if (null === $value) {
            return false;
        }

        $valueId = $this->getSingleIdentifierValue($value, $metadata);
        if (null === $valueId) {
            return false;
        }

        if (null === $existingValue) {
            return $isSingleValuedInverseSideAssociation;
        }

        if (!is_object($existingValue) || !is_a($existingValue, $metadata->name)) {
            return false;
        }

        $existingValueId = $this->getSingleIdentifierValue($existingValue, $metadata);

        return
            null === $existingValueId
            || (string)$valueId !== (string)$existingValueId;
    }

    private function isValueChanged(?object $value, ?object $existingValue, ClassMetadata $metadata): bool
    {
        if (null === $existingValue || !is_object($existingValue) || !is_a($existingValue, $metadata->name)) {
            return false;
        }

        $existingValueId = $this->getSingleIdentifierValue($existingValue, $metadata);
        if (null === $value) {
            return null !== $existingValueId;
        }
        if (null === $existingValueId) {
            return false;
        }

        $valueId = $this->getSingleIdentifierValue($value, $metadata);

        return
            null === $valueId
            || (string)$valueId !== (string)$existingValueId;
    }

    private function getExistingValue(object $entity, string $fieldName, EntityManagerInterface $em): mixed
    {
        $originalData = $em->getUnitOfWork()->getOriginalEntityData($entity);

        return $originalData[$fieldName] ?? null;
    }

    private function getSingleIdentifierValue(object $entity, ClassMetadata $metadata): mixed
    {
        $entityId = $metadata->getIdentifierValues($entity);

        return !empty($entityId) ? reset($entityId) : null;
    }
}
