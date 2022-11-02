<?php

namespace Oro\Bundle\EntityBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator for EntityFieldFallbackValueConstraint
 */
class RelatedEntityFieldFallbackValueConstraintValidator extends ConstraintValidator
{
    /** @var EntityFallbackResolver */
    private $fallbackResolver;

    public function __construct(EntityFallbackResolver $resolver)
    {
        $this->fallbackResolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($fallbackValue, Constraint $constraint)
    {
        /** @var RelatedEntityFieldFallbackValueConstraint $constraint */
        if (!$fallbackValue instanceof EntityFieldFallbackValue) {
            return;
        }
        $parentEntity = $this->context->getObject();
        if (!$parentEntity) {
            return;
        }
        $fieldName = $this->context->getPropertyName();
        if (!$fieldName) {
            return;
        }

        $fallbackId = $fallbackValue->getFallback();
        if ($fallbackId) {
            $this->validateFallbackId($fallbackId, $parentEntity, $fieldName);
            return;
        }

        $requiredValueField = $this->fallbackResolver->getRequiredFallbackFieldByType(
            $this->fallbackResolver->getType($parentEntity, $fieldName)
        );

        if (EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD === $requiredValueField) {
            $this->validateScalarField($fallbackValue->getScalarValue(), $constraint->scalarValueConstraints);
        }
    }

    /**
     * @param string $fallbackId
     * @param object $parentEntity
     * @param string $fieldName
     */
    private function validateFallbackId(string $fallbackId, $parentEntity, string $fieldName)
    {
        if ($this->fallbackResolver->isFallbackConfigured($fallbackId, $parentEntity, $fieldName)) {
            return;
        }
        $fallbackConfig = $this->fallbackResolver->getFallbackConfig(
            $parentEntity,
            $fieldName,
            EntityFieldFallbackValue::FALLBACK_LIST
        );
        $this->context->addViolation('oro.entity.entity_field_fallback_value.invalid_with_hint', [
            '%values%' => implode(', ', array_keys($fallbackConfig))
        ]);
    }

    /**
     * @param mixed $scalarValue
     * @param Constraint[] $constraints
     */
    private function validateScalarField($scalarValue, array $constraints)
    {
        if ($constraints) {
            $violations = $this->context
                ->getValidator()
                ->startContext()
                ->atPath($this->context->getPropertyPath('value'))
                ->validate($scalarValue, $constraints)
                ->getViolations();
            $this->context->getViolations()->addAll($violations);
        } elseif ($scalarValue === null) {
            $this->context->addViolation('oro.entity.entity_field_fallback_value.not_null');
        }
    }
}
