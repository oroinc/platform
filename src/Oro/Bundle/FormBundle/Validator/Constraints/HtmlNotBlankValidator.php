<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlankValidator;

class HtmlNotBlankValidator extends NotBlankValidator
{
    /**
     * @param HtmlNotBlank $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        parent::validate(strip_tags($value), $constraint);
    }
}
