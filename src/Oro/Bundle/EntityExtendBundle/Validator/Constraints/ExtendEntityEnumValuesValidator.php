<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Doctrine\Persistence\Proxy;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that enum field values exist in the system for extended entities.
 */
class ExtendEntityEnumValuesValidator extends ConstraintValidator
{
    public function __construct(private EnumOptionsProvider $enumOptionsProvider)
    {
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExtendEntityEnumValues) {
            throw new UnexpectedTypeException($constraint, ExtendEntityEnumValues::class);
        }

        if ($value === null) {
            return;
        }

        $options = is_array($value) ? $value : [$value];
        foreach ($options as $option) {
            $this->validateEnumOption($option, $constraint);
        }
    }

    /**
     * Validates single enum option.
     *
     * IMPORTANT: Do not call any methods on $option except getId() as they will trigger
     * proxy initialization and cause database queries during validation. Extract all required
     * data (enum code, internal ID) from the ID string using ExtendHelper methods.
     */
    private function validateEnumOption(mixed $option, ExtendEntityEnumValues $constraint): void
    {
        if (!$option instanceof EnumOptionInterface) {
            return;
        }

        // Skip validation if proxy is already initialized - entity exists in DB.
        if ($option instanceof Proxy && $option->__isInitialized()) {
            return;
        }

        $optionId = $option->getId();

        // Extract enum code from ID without calling getEnumCode() to avoid proxy initialization
        $enumCode = ExtendHelper::extractEnumCode($optionId);

        // Reload valid options for this specific enum code
        $validOptions = array_flip($this->enumOptionsProvider->getEnumChoicesByCode($enumCode));

        if (!array_key_exists($optionId, $validOptions)) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ value }}', ExtendHelper::getEnumInternalId($optionId))
                ->addViolation();
        }
    }
}
