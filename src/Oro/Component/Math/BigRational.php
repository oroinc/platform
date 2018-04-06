<?php

namespace Oro\Component\Math;

use Brick\Math\BigInteger as BrickBigInteger;
use Brick\Math\BigRational as BrickBigRational;

/**
 * An arbitrarily large rational number.
 *
 * This class is immutable.
 */
final class BigRational extends BigNumber
{
    /**
     * @var string Target Brick library class;
     */
    protected static $targetClass = BrickBigRational::class;

    /**
     * @var BrickBigRational;
     */
    protected $targetObject;

    /**
     * Creates a BigRational out of a numerator and a denominator.
     *
     * If the denominator is negative, the signs of both the numerator and the denominator
     * will be inverted to ensure that the denominator is always positive.
     *
     * @param BigNumber|number|string $numerator   The numerator. Must be convertible to a BigInteger.
     * @param BigNumber|number|string $denominator The denominator. Must be convertible to a BigInteger.
     *
     * @return BigRational
     */
    public static function nd($numerator, $denominator)
    {
        // Transform BigNumber input objects to BrickBigNumber objects
        self::convertToTargetObjects($numerator, $denominator);
        /** @var BrickBigRational $brickBigRational */
        $targetClass = static::$targetClass;
        $brickBigRational = $targetClass::nd($numerator, $denominator);

        return new static($brickBigRational);
    }

    /**
     * @return BigInteger
     */
    public function numerator()
    {
        /** @var BrickBigInteger $brickBigInteger */
        $brickBigInteger = $this->getTargetObject()->numerator();

        return new BigInteger($brickBigInteger);
    }

    /**
     * @return BigInteger
     */
    public function denominator()
    {
        /** @var BrickBigInteger $brickBigInteger */
        $brickBigInteger = $this->getTargetObject()->denominator();

        return new BigInteger($brickBigInteger);
    }

    /**
     * Returns the quotient of the division of the numerator by the denominator.
     *
     * @return BigInteger
     */
    public function quotient($that = null) // $that - only for compatible with parent class
    {
        /** @var BrickBigInteger $brickBigInteger */
        $brickBigInteger = $this->getTargetObject()->quotient();

        return new BigInteger($brickBigInteger);
    }

    /**
     * Returns the remainder of the division of the numerator by the denominator.
     *
     * @return BigInteger
     */
    public function remainder($that = null) // $that - only for compatible with parent class
    {
        /** @var BrickBigInteger $brickBigInteger */
        $brickBigInteger = $this->getTargetObject()->remainder();

        return new BigInteger($brickBigInteger);
    }

    /**
     * Returns the quotient and remainder of the division of the numerator by the denominator.
     *
     * @return BigInteger[]
     */
    public function quotientAndRemainder($that = null) // $that - only for compatible with parent class
    {
        /** @var BrickBigInteger[] $brickBigIntegerArray */
        $brickBigIntegerArray = $this->getTargetObject()->quotientAndRemainder();
        $quotient = new BigInteger($brickBigIntegerArray[0]);
        $reminder = new BigInteger($brickBigIntegerArray[1]);

        return [$quotient, $reminder];
    }

    /**
     * Returns the reciprocal of this BigRational.
     *
     * The reciprocal has the numerator and denominator swapped.
     *
     * @return BigRational
     */
    public function reciprocal()
    {
        /** @var BrickBigRational $brickBigRational */
        $brickBigRational = $this->getTargetObject()->reciprocal();

        return new static($brickBigRational);
    }

    /**
     * Returns the simplified value of this BigRational.
     *
     * @return BigRational
     */
    public function simplified()
    {
        /** @var BrickBigRational $brickBigRational */
        $brickBigRational = $this->getTargetObject()->simplified();

        return new static($brickBigRational);
    }

    /**
     * {@inheritdoc}
     */
    public function toBigRational()
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

        // Create the "targetObject" - BrickBigRational instance
        list($numerator, $denominator) = explode('/', $serialized);
        $numerator   = BrickBigInteger::of($numerator);
        $denominator = BrickBigInteger::of($denominator);
        $targetClass = static::$targetClass;
        /** @var BrickBigRational $brickBigRational */
        $brickBigRational = $targetClass::of($numerator, $denominator, true);
        $this->targetObject = $brickBigRational;
    }
}
