<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * The validator for UnchangeableField constraint.
 */
class UnchangeableFieldValidator extends ConstraintValidator
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $entityClass = $this->context->getClassName();
        $em = $this->doctrineHelper->getEntityManagerForClass($entityClass);
        $metadata = $em->getClassMetadata($entityClass);

        if ($metadata->hasAssociation($this->context->getPropertyName())) {
            if ((null === $value || is_object($value)) && $this->isAssociationValueChanged($value, $em, $metadata)) {
                $this->context->addViolation($constraint->message);
            }
        } elseif ($this->isFieldValueChanged($value, $em)) {
            $this->context->addViolation($constraint->message);
        }
    }

    /**
     * @param mixed                  $value
     * @param EntityManagerInterface $em
     *
     * @return bool
     */
    private function isFieldValueChanged($value, EntityManagerInterface $em): bool
    {
        $existingValue = $this->getExistingValue($em);

        return
            null !== $existingValue
            && $existingValue !== $value;
    }

    /**
     * @param object|null            $value
     * @param EntityManagerInterface $em
     * @param ClassMetadata          $metadata
     *
     * @return bool
     */
    private function isAssociationValueChanged($value, EntityManagerInterface $em, ClassMetadata $metadata): bool
    {
        $targetEntityClass = $metadata->getAssociationTargetClass($this->context->getPropertyName());
        if (is_object($value) && !is_a($value, $targetEntityClass)) {
            return false;
        }

        $targetMetadata = $em->getClassMetadata($targetEntityClass);
        if ($targetMetadata->isIdentifierComposite) {
            throw new \LogicException(sprintf(
                '%s is not allowed to be used for %s::%s'
                . ' because the target entity %s has composite identifier.',
                self::class,
                $this->context->getClassName(),
                $this->context->getPropertyName(),
                $targetEntityClass
            ));
        }

        $existingValue = $this->getExistingValue($em);
        if (null === $existingValue || !is_object($existingValue) || !is_a($existingValue, $targetEntityClass)) {
            return false;
        }

        $existingValueId = $this->getSingleIdentifierValue($existingValue, $targetMetadata);
        if (null === $value) {
            return null !== $existingValueId;
        }
        if (null === $existingValueId) {
            return false;
        }

        $valueId = $this->getSingleIdentifierValue($value, $targetMetadata);

        return
            null === $valueId
            || (string)$valueId !== (string)$existingValueId;
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @return mixed
     */
    private function getExistingValue(EntityManagerInterface $em)
    {
        $originalData = $em->getUnitOfWork()->getOriginalEntityData($this->context->getObject());

        return $originalData[$this->context->getPropertyName()] ?? null;
    }

    /**
     * @param object        $object
     * @param ClassMetadata $metadata
     *
     * @return mixed
     */
    private function getSingleIdentifierValue($object, ClassMetadata $metadata)
    {
        $objectId = $metadata->getIdentifierValues($object);

        return !empty($objectId) ? reset($objectId) : null;
    }
}
