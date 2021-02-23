<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * This constraint is used to check whether a percentage value is in a specific range.
 * The "type" option defines how the checked value represents a percentage value, possible values:
 * * "fractional" (it is default value) - a percentage value is stored as a float,  100% equals to 1.
 * * "fractional_100" - a percentage value is stored as a float, 100% equals to 100.
 * * "integer" - a percentage value is stored as an integer, 100% equals to 1.
 * The "min" and "max" options should be specified in a percents, it means that 100% equals to 100,
 * independs on the "type" option.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class PercentRange extends Constraint
{
    public const FRACTIONAL     = 'fractional';
    public const FRACTIONAL_100 = 'fractional_100';
    public const INTEGER        = 'integer';

    private const TYPES = [self::FRACTIONAL, self::FRACTIONAL_100, self::INTEGER];

    public $minMessage = 'This value should be {{ limit }} or more.';
    public $maxMessage = 'This value should be {{ limit }} or less.';
    public $notInRangeMessage = 'This value should be between {{ min }} and {{ max }}.';
    public $invalidMessage = 'This value should be a valid number.';
    public $notIntegerMessage = 'This value should be an integer number.';

    /** @var float|int|null */
    public $min;

    /** @var float|int|null */
    public $max;

    /** @var string */
    public $type = self::FRACTIONAL;

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

        if (!\in_array($this->type, self::TYPES, true)) {
            throw new ConstraintDefinitionException(sprintf(
                'The option "type" for the constraint "%s" must be one of "%s".',
                static::class,
                implode('", "', self::TYPES)
            ));
        }
        if (null === $this->min && null === $this->max) {
            throw new MissingOptionsException(
                sprintf('Either option "min" or "max" must be given for the constraint "%s".', static::class),
                ['min', 'max']
            );
        }
    }
}
