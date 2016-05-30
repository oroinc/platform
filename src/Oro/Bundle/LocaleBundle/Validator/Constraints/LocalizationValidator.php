<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\LocaleBundle\Validator\Constraints;
use Oro\Bundle\LocaleBundle\Entity;

class LocalizationValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param Entity\Localization $localization
     * @param Constraints\Localization $constraint
     */
    public function validate($localization, Constraint $constraint)
    {
        if (!$localization instanceof Entity\Localization) {
            throw new UnexpectedTypeException(
                $localization,
                'Oro\Bundle\LocaleBundle\Entity\Localization'
            );
        }
        $parentLocalization = $localization->getParentLocalization();

        if (!$parentLocalization) {
            return;
        }
        if ($localization->getId() === $parentLocalization->getId() ||
            $localization->getChildLocalizations()->contains($parentLocalization)
        ) {
            $this->context->buildViolation($constraint->messageCircularReference)
                ->atPath('parentLocalization')
                ->addViolation();
        }
    }
}
