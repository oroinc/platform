<?php

namespace Oro\Bundle\InstallerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraints as Assert;

class ExtensionLoadedValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (is_array($value) || is_object($value)) {
            throw new \InvalidArgumentException(sprintf('Value is type of %s, string is required', gettype($value)));
        }

        $value = (string)$value;

        /** @var ExtensionLoaded $constraint */
        if (!extension_loaded($value)) {
            $this->context->addViolation($constraint->message, ['%extension%' => $value]);
        }
    }
}
