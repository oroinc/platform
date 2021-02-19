<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * This constraint is used to check whether a percentage value is in a specific range.
 * The "min" and "max" options should be specified in a percents,
 * it means that 100% equals to 100, not 1.
 * The "fractional" option defines how the checked value represents a percentage value.
 * When the "fractional" option is TRUE (it is default value) than 100% equals to 1.
 * When the "fractional" option is FALSE than 100% equals to 100.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class PercentRange extends Constraint
{
    public $minMessage = 'This value should be {{ limit }} or more.';
    public $maxMessage = 'This value should be {{ limit }} or less.';
    public $notInRangeMessage = 'This value should be between {{ min }} and {{ max }}.';
    public $invalidMessage = 'This value should be a valid number.';

    /** @var float|int|null */
    public $min;

    /** @var float|int|null */
    public $max;

    /** @var bool */
    public $fractional = true;

    public function __construct(array $options = null)
    {
        if (\is_array($options) && isset($options['min'], $options['max'])) {
            if (isset($options['minMessage']) || isset($options['maxMessage'])) {
                throw new ConstraintDefinitionException(sprintf(
                    'The "%s" constraint does not use the "minMessage" and "maxMessage" options'
                    . ' when the "min" and "max" options are both set. Use the "notInRangeMessage" option instead.',
                    static::class
                ));
            }
        }

        parent::__construct($options);

        if (null === $this->min && null === $this->max) {
            throw new MissingOptionsException(
                sprintf('Either option "min" or "max" must be given for constraint "%s".', static::class),
                ['min', 'max']
            );
        }
    }
}
