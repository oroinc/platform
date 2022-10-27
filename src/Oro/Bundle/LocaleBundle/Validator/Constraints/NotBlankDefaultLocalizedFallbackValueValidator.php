<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Custom validator for the localized fallback default value.
 *
 * This approach was used instead of getter validation because: at current moment we could not define custom method
 * for the property in yml. It could be done only in entity annotations which is unconvinient,
 * and one more possible solution is to define not existing property which is even worse solution from my point of view
 * To get more information about the topic look at usages of the:
 * @see \Symfony\Component\Validator\Mapping\ClassMetadata::addGetterMethodConstraint
 * and usages of the:
 * @see \Symfony\Component\Validator\Mapping\ClassMetadata::addGetterConstraint
 * in:
 * @see \Symfony\Component\Validator\Mapping\Loader\YamlFileLoader::loadClassMetadataFromYaml
 * and
 * @see \Symfony\Component\Validator\Mapping\Loader\AnnotationLoader::loadClassMetadata
 *
 * If this behaviour will be changed in the future getter validation will
 * be more preferable than custom property validation, so this code should be refactored in such case
 */
class NotBlankDefaultLocalizedFallbackValueValidator extends ConstraintValidator
{
    use FallbackTrait;

    /**
     * @param null|Collection|AbstractLocalizedFallbackValue[] $value
     * @param NotBlankDefaultLocalizedFallbackValue|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NotBlankDefaultLocalizedFallbackValue) {
            throw new UnexpectedTypeException($constraint, NotBlankDefaultLocalizedFallbackValue::class);
        }

        if (!$value instanceof Collection) {
            throw new UnexpectedValueException($value, Collection::class);
        }

        $defaultValue = $this->getDefaultFallbackValue($value);
        if ($defaultValue === null || $this->isLocalizationEmpty($defaultValue)) {
            $this->context->buildViolation($constraint->errorMessage)->addViolation();
        }
    }

    private function isLocalizationEmpty(AbstractLocalizedFallbackValue $localizedFallbackValue): bool
    {
        $notEmptyValues = array_filter(
            [$localizedFallbackValue->getString(), $localizedFallbackValue->getText()],
            static function ($value) {
                $value = trim($value ?? '');

                return '0' === $value || !empty($value);
            }
        );

        return 0 === count($notEmptyValues);
    }
}
