<?php

namespace Oro\Component\Math;

use Brick\Math\BigDecimal as BrickBigDecimal;

/**
 * Immutable, arbitrary-precision signed decimal numbers.
 */
final class BigDecimal extends BigNumber
{
    /**
     * @var string Target Brick library class;
     */
    protected static $targetClass = BrickBigDecimal::class;

    /**
     * @var BrickBigDecimal;
     */
    protected $targetObject;

    /**
     * Creates a BigDecimal from an unscaled value and a scale.
     *
     * Example: `(12345, 3)` will result in the BigDecimal `12.345`.
     *
     * @param BigNumber|number|string $value The unscaled value. Must be convertible to a BigInteger.
     * @param int                     $scale The scale of the number, positive or zero.
     *
     * @return BigDecimal
     */
    public static function ofUnscaledValue($value, $scale = 0)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($value, $scale);
        /** @var BrickBigDecimal $brickBigDecimal */
        $targetClass = static::$targetClass;
        $brickBigDecimal = $targetClass::ofUnscaledValue($value, $scale);

        return new self($brickBigDecimal);
    }

    /**
     * Returns the result of the division of this number by the given one, at the given scale.
     *
     * @param BigNumber|number|string $divisor      The divisor.
     * @param int|null                $scale        The desired scale, or null to use the scale of this number.
     * @param int                     $roundingMode An optional rounding mode.
     *
     * @return BigDecimal
     */
    public function dividedBy($divisor, $scale = null, $roundingMode = RoundingMode::UNNECESSARY)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($divisor);
        /** @var BrickBigDecimal $brickBigDecimal */
        $brickBigDecimal = $this->getTargetObject()->dividedBy($divisor, $scale, $roundingMode);

        return new self($brickBigDecimal);
    }

    /**
     * Returns the exact result of the division of this number by the given one.
     *
     * The scale of the result is automatically calculated to fit all the fraction digits.
     *
     * @param BigNumber|number|string $divisor The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The result.
     *                             or the result yields an infinite number of digits.
     */
    public function exactlyDividedBy($divisor)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($divisor);
        /** @var BrickBigDecimal $brickBigDecimal */
        $brickBigDecimal = $this->getTargetObject()->exactlyDividedBy($divisor);

        return new self($brickBigDecimal);
    }

    /**
     * Returns a BigDecimal with the current value and the specified scale.
     *
     * @deprecated Use `toScale()`.
     *
     * @param int $scale
     * @param int $roundingMode
     *
     * @return BigDecimal
     */
    public function withScale($scale, $roundingMode = RoundingMode::UNNECESSARY)
    {
        /** @var BrickBigDecimal $brickBigDecimal */
        $brickBigDecimal = $this->getTargetObject()->withScale($scale, $roundingMode);

        return new self($brickBigDecimal);
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the left.
     *
     * @param int $number
     *
     * @return BigDecimal
     */
    public function withPointMovedLeft($number)
    {
        /** @var BrickBigDecimal $brickBigDecimal */
        $brickBigDecimal = $this->getTargetObject()->withPointMovedLeft($number);

        return new self($brickBigDecimal);
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the right.
     *
     * @param int $number
     *
     * @return BigDecimal
     */
    public function withPointMovedRight($number)
    {
        /** @var BrickBigDecimal $brickBigDecimal */
        $brickBigDecimal = $this->getTargetObject()->withPointMovedRight($number);

        return new self($brickBigDecimal);
    }

    /**
     * Returns a copy of this BigDecimal with any trailing zeros removed from the fractional part.
     *
     * @return BigDecimal
     */
    public function stripTrailingZeros()
    {
        /** @var BrickBigDecimal $brickBigDecimal */
        $brickBigDecimal = $this->getTargetObject()->stripTrailingZeros();

        return new self($brickBigDecimal);
    }

    /**
     * Returns the unscaled value
     *
     * @return string
     */
    public function unscaledValue()
    {
        return $this->getTargetObject()->unscaledValue();
    }

    /**
     * Returns the scale
     *
     * @return int
     */
    public function scale()
    {
        return $this->getTargetObject()->scale();
    }

    /**
     * Returns a string representing the integral part of this decimal number.
     *
     * Example: `-123.456` => `-123`.
     *
     * @return string
     */
    public function integral()
    {
        return $this->getTargetObject()->integral();
    }

    /**
     * Returns a string representing the fractional part of this decimal number.
     *
     * If the scale is zero, an empty string is returned.
     *
     * Examples: `-123.456` => '456', `123` => ''.
     *
     * @return string
     */
    public function fraction()
    {
        return $this->getTargetObject()->fraction();
    }

    /**
     * {@inheritdoc}
     */
    public function toBigDecimal()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        if ($this->getTargetObject() !== null) {
            throw new \LogicException('unserialize() must not be called directly.');
        }

        list($value, $scale) = explode(':', $serialized);
        /** @var BrickBigDecimal $brickBigDecimal */
        $targetClass = static::$targetClass;
        $brickBigDecimal = $targetClass::of($value)->toScale($scale);
        $this->targetObject = $brickBigDecimal;
    }
}
