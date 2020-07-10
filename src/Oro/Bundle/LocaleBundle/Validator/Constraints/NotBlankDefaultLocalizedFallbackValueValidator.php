<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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
     * @param null|Collection|LocalizedFallbackValue[] $value
     * @param NotBlankDefaultLocalizedFallbackValue|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $defaultValue = $this->getDefaultFallbackValue($value);
        if ($defaultValue === null) {
            $this->context->buildViolation($constraint->errorMessage)->addViolation();
        }
    }
}
