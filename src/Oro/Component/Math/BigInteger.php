<?php

namespace Oro\Component\Math;

use Brick\Math\BigInteger as BrickBigInteger;

/**
 * An arbitrary-size integer.
 *
 * All methods accepting a number as a parameter accept either a BigInteger instance,
 * an integer, or a string representing an arbitrary size integer.
 */
final class BigInteger extends BigNumber
{
    /**
     * @var string Target Brick library class;
     */
    protected static $targetClass = BrickBigInteger::class;

    /**
     * @var BrickBigInteger;
     */
    protected $targetObject;

    /**
     * Parses a string containing an integer in an arbitrary base.
     *
     * The string can optionally be prefixed with the `+` or `-` sign.
     *
     * @param string $number The number to parse.
     * @param int    $base   The base of the$ number, between 2 and 36.
     *
     * @return BigInteger
     */
    public static function parse($number, $base = 10)
    {
        /** @var BrickBigInteger $brickBigInteger */
        $targetClass = static::$targetClass;
        $brickBigInteger = $targetClass::parse($number, $base);

        return new self($brickBigInteger);
    }

    /**
     * Returns the greatest common divisor of this number and the given one.
     *
     * The GCD is always positive, unless both operands are zero, in which case it is zero.
     *
     * @param BigNumber|number|string $divisor The operand. Must be convertible to an integer number.
     *
     * @return BigInteger
     */
    public function gcd($divisor)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($divisor);
        /** @var BrickBigInteger $brickBigInteger */
        $brickBigInteger = $this->getTargetObject()->gcd($divisor);

        return new self($brickBigInteger);
    }

    /**
     * Returns a string representation of this number in the given base.
     *
     * @param int $base
     *
     * @return string
     */
    public function toBase($base)
    {
        return $this->getTargetObject()->toBase($base);
    }

    /**
     * {@inheritdoc}
     */
    public function toBigInteger()
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

        /** @var BrickBigInteger $brickBigInteger */
        $targetClass = static::$targetClass;
        $brickBigInteger = $targetClass::of($serialized);
        $this->targetObject = $brickBigInteger;
    }
}
