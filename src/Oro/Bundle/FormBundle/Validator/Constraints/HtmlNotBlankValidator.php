<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlankValidator;

/**
 * Validates that HTML field has content (text or image) wrapped with html tags.
 */
class HtmlNotBlankValidator extends NotBlankValidator
{
    /**
     * @param HtmlNotBlank $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        parent::validate(preg_replace('/\s/u', '', strip_tags($value, '<img>')), $constraint);
    }
}
