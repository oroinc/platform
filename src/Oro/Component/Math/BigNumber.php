<?php

namespace Oro\Component\Math;

use Brick\Math\BigDecimal as BrickBigDecimal;
use Brick\Math\BigInteger as BrickBigInteger;
use Brick\Math\BigNumber as BrickBigNumber;
use Brick\Math\BigRational as BrickBigRational;

/**
 * Common interface for arbitrary-precision rational numbers.
 */
abstract class BigNumber implements \Serializable, \JsonSerializable
{
    /**
     * @var string Target Brick library class;
     */
    protected static $targetClass = BrickBigNumber::class;

    /**
     * @var BrickBigNumber;
     */
    protected $targetObject;

    /**
     * Creates a BigNumber child classes instance of the given value.
     *
     * @param BigNumber|number|string $value
     *
     * @return static
     */
    public static function of($value)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($value);
        /** @var BrickBigNumber $targetClass */
        $targetClass = static::$targetClass;
        $brickBigNumber = $targetClass::of($value);

        return new static($brickBigNumber);
    }

    /**
     * Returns a BigDecimal representing zero, with a scale of zero.
     *
     * @return static
     */
    public static function zero()
    {
        /** @var BrickBigNumber $brickBigNumber */
        $targetClass = static::$targetClass;
        $brickBigNumber = $targetClass::zero();

        return new static($brickBigNumber);
    }

    /**
     * Returns a BigDecimal representing one, with a scale of zero.
     *
     * @return static
     */
    public static function one()
    {
        /** @var BrickBigNumber $brickBigNumber */
        $targetClass = static::$targetClass;
        $brickBigNumber = $targetClass::one();

        return new static($brickBigNumber);
    }

    /**
     * Returns a BigDecimal representing ten, with a scale of zero.
     *
     * @return static
     */
    public static function ten()
    {
        /** @var BrickBigNumber $brickBigNumber */
        $targetClass = static::$targetClass;
        $brickBigNumber = $targetClass::ten();

        return new static($brickBigNumber);
    }

    /**
     * Returns the minimum of the given values.
     *
     * @param BigNumber|number|string ...$values The numbers to compare. All the numbers need to be convertible
     *                                           to an instance of the class this method is called on.
     *
     * @return static The minimum value.
     */
    public static function min(...$values)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects(...$values);

        // Calculate min input value with the BrickBigNumber::min method
        $targetClass = static::$targetClass;

        return new static($targetClass::min(...$values));
    }

    /**
     * Returns the maximum of the given values.
     *
     * @param BigNumber|number|string ...$values The numbers to compare. All the numbers need to be convertible
     *                                           to an instance of the class this method is called on.
     *
     * @return static The maximum value.
     */
    public static function max(...$values)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects(...$values);

        // Calculate max input value with the BrickBigNumber::max method
        $targetClass = static::$targetClass;

        return new static($targetClass::max(...$values));
    }

    /**
     * Transforms BigNumber objects in $input array to BrickBigNumber objects
     * (to use them in BrickBigNumber methods)
     *
     * @param array $input
     */
    protected static function convertToTargetObjects(&...$input)
    {
        array_walk($input, function (&$item, $key) {
            if ($item instanceof BigNumber) {
                $item = $item->getTargetObject();
            }
        });
    }

    /**
     * BigNumber constructor.
     *
     * @param BrickBigNumber $brickBigNumber
     */
    protected function __construct(BrickBigNumber $brickBigNumber)
    {
        $this->targetObject = $brickBigNumber;
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $divisor->scale)`.
     *
     * @param BigNumber|number|string $divisor The number to add. Must be convertible to a BigDecimal.
     *
     * @return static The result.
     */
    public function plus($divisor)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($divisor);
        /** @var BrickBigNumber $brickBigNumber */
        $brickBigNumber = $this->getTargetObject()->plus($divisor);

        return new static($brickBigNumber);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $divisor->scale)`.
     *
     * @param BigNumber|number|string $divisor The number to subtract. Must be convertible to a BigDecimal.
     *
     * @return static The result.
     */
    public function minus($divisor)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($divisor);
        /** @var BrickBigNumber $brickBigNumber */
        $brickBigNumber = $this->getTargetObject()->minus($divisor);

        return new static($brickBigNumber);
    }

    /**
     * Returns the product of this number and the given one.
     *
     * The result has a scale of `$this->scale + $divisor->scale`.
     *
     * @param BigNumber|number|string $divisor The multiplier. Must be convertible to a BigDecimal.
     *
     * @return static The result.
     */
    public function multipliedBy($divisor)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($divisor);
        /** @var BrickBigNumber $brickBigNumber */
        $brickBigNumber = $this->getTargetObject()->multipliedBy($divisor);

        return new static($brickBigNumber);
    }

    /**
     * Returns this number exponentiated to the given value.
     *
     * The result has a scale of `$this->scale * $exponent`.
     *
     * @param int $exponent The exponent.
     *
     * @return static The result.
     */
    public function power($exponent)
    {
        /** @var BrickBigNumber $brickBigNumber */
        $brickBigNumber = $this->getTargetObject()->power($exponent);

        return new static($brickBigNumber);
    }

    /**
     * Returns the quotient of the division of this number by this given one.
     *
     * The quotient has a scale of `0`.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return static The quotient.
     */
    public function quotient($that)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($that);
        /** @var BrickBigNumber $brickBigNumber */
        $brickBigNumber = $this->getTargetObject()->quotient($that);

        return new static($brickBigNumber);
    }

    /**
     * Returns the remainder of the division of this number by this given one.
     *
     * The remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return static The remainder.
     */
    public function remainder($that)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($that);
        /** @var BrickBigNumber $brickBigNumber */
        $brickBigNumber = $this->getTargetObject()->remainder($that);

        return new static($brickBigNumber);
    }

    /**
     * Returns the quotient and remainder of the division of this number by the given one.
     *
     * The quotient has a scale of `0`, and the remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return static[] An array containing the quotient and the remainder.
     */
    public function quotientAndRemainder($that)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($that);
        /** @var BrickBigNumber $brickBigNumber */
        $brickBigNumberArray = $this->getTargetObject()->quotientAndRemainder($that);
        $quotient = new static($brickBigNumberArray[0]);
        $remainder = new static($brickBigNumberArray[1]);

        return [$quotient, $remainder];
    }

    /**
     * Checks if this number is equal to the given one.
     *
     * @param BigNumber|number|string $divisor
     *
     * @return bool
     */
    public function isEqualTo($divisor)
    {
        // Transform BigNumber input object to BrickBigNumber object
        self::convertToTargetObjects($divisor);

        return $this->getTargetObject()->isEqualTo($divisor);
    }

    /**
     * Checks if this number is strictly lower than the given one.
     *
     * @param BigNumber|number|string $divisor
     *
     * @return bool
     */
    public function isLessThan($divisor)
    {
        // Transform BigNumber input object to BrickBigNumber object
        self::convertToTargetObjects($divisor);

        return $this->getTargetObject()->isLessThan($divisor);
    }

    /**
     * Checks if this number is lower than or equal to the given one.
     *
     * @param BigNumber|number|string $divisor
     *
     * @return bool
     */
    public function isLessThanOrEqualTo($divisor)
    {
        // Transform BigNumber input object to BrickBigNumber object
        self::convertToTargetObjects($divisor);

        return $this->getTargetObject()->isLessThanOrEqualTo($divisor);
    }

    /**
     * Checks if this number is strictly greater than the given one.
     *
     * @param BigNumber|number|string $divisor
     *
     * @return bool
     */
    public function isGreaterThan($divisor)
    {
        // Transform BigNumber input object to BrickBigNumber object
        self::convertToTargetObjects($divisor);

        return $this->getTargetObject()->isGreaterThan($divisor);
    }

    /**
     * Checks if this number is greater than or equal to the given one.
     *
     * @param BigNumber|number|string $divisor
     *
     * @return bool
     */
    public function isGreaterThanOrEqualTo($divisor)
    {
        // Transform BigNumber input object to BrickBigNumber object
        self::convertToTargetObjects($divisor);

        return $this->getTargetObject()->isGreaterThanOrEqualTo($divisor);
    }

    /**
     * Checks if this number equals zero.
     *
     * @return bool
     */
    public function isZero()
    {
        return $this->getTargetObject()->isZero();
    }

    /**
     * Checks if this number is strictly negative.
     *
     * @return bool
     */
    public function isNegative()
    {
        return $this->getTargetObject()->isNegative();
    }

    /**
     * Checks if this number is negative or zero.
     *
     * @return bool
     */
    public function isNegativeOrZero()
    {
        return $this->getTargetObject()->isNegativeOrZero();
    }

    /**
     * Checks if this number is strictly positive.
     *
     * @return bool
     */
    public function isPositive()
    {
        return $this->getTargetObject()->isPositive();
    }

    /**
     * Checks if this number is positive or zero.
     *
     * @return bool
     */
    public function isPositiveOrZero()
    {
        return $this->getTargetObject()->isPositiveOrZero();
    }

    /**
     * Returns the absolute value of this number.
     *
     * @return static
     */
    public function abs()
    {
        /** @var BrickBigNumber $brickBigNumber */
        $brickBigNumber = $this->getTargetObject()->abs();

        return new static($brickBigNumber);
    }

    /**
     * Returns the negated value of this number.
     *
     * @return static
     */
    public function negated()
    {
        /** @var BrickBigNumber $brickBigNumber */
        $brickBigNumber = $this->getTargetObject()->negated();

        return new static($brickBigNumber);
    }

    /**
     * Returns the sign of this number.
     *
     * @return int -1 if the number is negative, 0 if zero, 1 if positive.
     */
    public function sign()
    {
        return $this->getTargetObject()->sign();
    }

    /**
     * Compares this number to the given one.
     *
     * @param BigNumber|number|string $divisor
     *
     * @return int [-1,0,1] If `$this` is lower than, equal to, or greater than `$divisor`.
     */
    public function compareTo($divisor)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($divisor);

        return $this->getTargetObject()->compareTo($divisor);
    }

    /**
     * Converts this number to a BigInteger.
     *
     * @return BigInteger The converted number.
     */
    public function toBigInteger()
    {
        /** @var BrickBigInteger $brickBigInteger */
        $brickBigInteger = $this->getTargetObject()->toBigInteger();

        return new BigInteger($brickBigInteger);
    }

    /**
     * Converts this number to a BigDecimal.
     *
     * @return BigDecimal The converted number.
     */
    public function toBigDecimal()
    {
        /** @var BrickBigDecimal $brickBigDecimal */
        $brickBigDecimal = $this->getTargetObject()->toBigDecimal();

        return new BigDecimal($brickBigDecimal);
    }

    /**
     * Converts this number to a BigRational.
     *
     * @return BigRational The converted number.
     */
    public function toBigRational()
    {
        /** @var BrickBigRational $brickBigRational */
        $brickBigRational = $this->getTargetObject()->toBigRational();

        return new BigRational($brickBigRational);
    }

    /**
     * Converts this number to a BigDecimal with the given scale, using rounding if necessary.
     *
     * @param int $scale        The scale of the resulting `BigDecimal`.
     * @param int $roundingMode A `RoundingMode` constant.
     *
     * @return BigDecimal
     */
    public function toScale($scale, $roundingMode = RoundingMode::UNNECESSARY)
    {
        /** @var BrickBigDecimal $brickBigDecimal */
        $brickBigDecimal = $this->getTargetObject()->toScale($scale, $roundingMode);

        return new BigDecimal($brickBigDecimal);
    }

    /**
     * Returns the exact value of this number as a native integer.
     *
     * If this number cannot be converted to a native integer without losing precision, an exception is thrown.
     * Note that the acceptable range for an integer depends on the platform and differs for 32-bit and 64-bit.
     *
     * @return int The converted value.
     */
    public function toInteger()
    {
        return $this->getTargetObject()->toInteger();
    }

    /**
     * Returns an approximation of this number as a floating-point value.
     *
     * Note that this method can discard information as the precision of a floating-point value
     * is inherently limited.
     *
     * @return float The converted value.
     */
    public function toFloat()
    {
        return $this->getTargetObject()->toFloat();
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return $this->getTargetObject()->serialize();
    }

    /**
     * Returns a string representation of this number.
     *
     * The output of this method can be parsed by the `of()` factory method;
     * this will yield an object equal to this one, without any information loss.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getTargetObject()->__toString();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    abstract public function unserialize($serialized);

    /**
     * Returns target Brick BigNumber object for this instance
     *
     * @return BrickBigNumber
     */
    protected function getTargetObject()
    {
        return $this->targetObject;
    }
}
