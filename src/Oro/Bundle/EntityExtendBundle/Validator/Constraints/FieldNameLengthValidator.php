<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\LengthValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for fieldName length, uses name generator service to get max length constraint.
 */
class FieldNameLengthValidator extends LengthValidator
{
    /** @var ExtendDbIdentifierNameGenerator */
    private $nameGenerator;

    /**
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(ExtendDbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof FieldNameLength) {
            throw new UnexpectedTypeException($constraint, FieldNameLength::class);
        }

        $constraint->min = FieldNameLength::MIN_LENGTH;
        $constraint->max = $this->nameGenerator->getMaxCustomEntityFieldNameSize();

        parent::validate($value, $constraint);
    }
}
