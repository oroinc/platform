# Oro Math Component

A library to work with arbitrary precision numbers. Currently based on the [Brick\Math](https://github.com/brick/math) library.

## Package contents

This library provides the following public classes in the Oro\Component\Math namespace:

- [BigNumber](./BigNumber.php): base class for `BigInteger`, `BigDecimal` and `BigRational`
- [BigInteger](./BigInteger.php): represents an arbitrary-precision integer number.
- [BigDecimal](./BigDecimal.php): represents an arbitrary-precision decimal number.
- [BigRational](./BigRational.php): represents an arbitrary-precision rational number (fraction).
- [RoundingMode](./RoundingMode.php): holds constants for the rounding modes.

## Overview

### Instantiation

The constructors of the classes are not public, you must use a factory method to obtain an instance.

All classes provide an `of()` factory method that accepts any of the following types:

- `BigNumber` instances
- `int` numbers
- `float` numbers
- `string` representations of integer, decimal and rational numbers

Example:

```php
BigInteger::of(123546);
BigInteger::of('9999999999999999999999999999999999999999999');

BigDecimal::of(1.2);
BigDecimal::of('9.99999999999999999999999999999999999999999999');

BigRational::of('2/3');
BigRational::of('1.1'); // 11/10
```

Note that all `of()` methods accept all of the representations above, *as long as it can be safely converted to
the current type*:

```php
BigInteger::of('1.00'); // 1
BigInteger::of('1.01'); // ArithmeticException

BigDecimal::of('1/8'); // 0.125
BigDecimal::of('1/3'); // ArithmeticException
```

Note about native integers: instantiating from an `int` is safe *as long as you don't exceed the maximum
value for your platform* (`PHP_INT_MAX`), in which case it would be transparently converted to `float` by PHP without
notice, and could result in a loss of information. In doubt, prefer instantiating from a `string`, which supports
an unlimited numbers of digits:

```php
echo BigInteger::of(999999999999999999999); // 1000000000000000000000
echo BigInteger::of('999999999999999999999'); // 999999999999999999999
```

Note about floating-point values: instantiating from a `float` might be unsafe, as floating-point values are
imprecise by design, and could result in a loss of information. Always prefer instantiating from a `string`, which
supports an unlimited number of digits:

```php
echo BigDecimal::of(1.99999999999999999999); // 2
echo BigDecimal::of('1.99999999999999999999'); // 1.99999999999999999999
```

### Immutability & chaining

The `BigInteger`, `BigDecimal` and `BigRational` classes are immutable: their value never changes,
so that they can be safely passed around. All methods that return a `BigInteger`, `BigDecimal` or `BigRational`
return a new object, leaving the original object unaffected:

```php
$ten = BigInteger::of(10);

echo $ten->plus(5); // 15
echo $ten->multipliedBy(3); // 30
```

The methods can be chained for better readability:

```php
echo BigInteger::of(10)->plus(5)->multipliedBy(3); // 45
```

### Parameter types

All methods that accept a number: `plus()`, `minus()`, `multipliedBy()`, etc. accept the same types as `of()`.
For example, given the following number:

```php
$integer = BigInteger::of(123);
```

The following lines are equivalent:

```php
$integer->multipliedBy(123);
$integer->multipliedBy('123');
$integer->multipliedBy($integer);
```

Just like `of()`, other types of `BigNumber` are acceptable, as long as they can be safely converted to the current type:

```php
echo BigInteger::of(2)->multipliedBy(BigDecimal::of('2.0')); // 4
echo BigInteger::of(2)->multipliedBy(BigDecimal::of('2.5')); // ArithmeticException
echo BigDecimal::of(2.5)->multipliedBy(BigInteger::of(2)); // 5.0
```

### Division & rounding

#### BigInteger

By default, dividing a `BigInteger` returns the exact result of the division, or throws an exception if the remainder
of the division is not zero:

```php
echo BigInteger::of(999)->dividedBy(3); // 333
echo BigInteger::of(1000)->dividedBy(3); // RoundingNecessaryException
```

You can pass an optional [rounding mode](http://brick.io/math/class-Brick.Math.RoundingMode.html) to round the result, if necessary:

```php
echo BigInteger::of(1000)->dividedBy(3, RoundingMode::DOWN); // 333
echo BigInteger::of(1000)->dividedBy(3, RoundingMode::UP); // 334
```

If you're into quotients and remainders, there are methods for this, too:

```php
echo BigInteger::of(1000)->quotient(3); // 333
echo BigInteger::of(1000)->remainder(3); // 1
```

You can even get both at the same time:

```php
list ($quotient, $remainder) = BigInteger::of(1000)->quotientAndRemainder(3);
```

#### BigDecimal

Dividing a `BigDecimal` always requires a scale to be specified. If the exact result of the division does not fit in
the given scale, a [rounding mode](http://brick.io/math/class-Brick.Math.RoundingMode.html) must be provided.

```php
echo BigDecimal::of(1)->dividedBy('8', 3); // 0.125
echo BigDecimal::of(1)->dividedBy('8', 2); // RoundingNecessaryException
echo BigDecimal::of(1)->dividedBy('8', 2, RoundingMode::HALF_DOWN); // 0.12
echo BigDecimal::of(1)->dividedBy('8', 2, RoundingMode::HALF_UP); // 0.13
```

If you know that the division yields a finite number of decimals places, you can use `exactlyDividedBy()`, which will
automatically compute the required scale to fit the result, or throw an exception if the division yields an infinite
repeating decimal:

```php
echo BigDecimal::of(1)->exactlyDividedBy(256); // 0.00390625
echo BigDecimal::of(1)->exactlyDividedBy(11); // RoundingNecessaryException
```

#### BigRational

The result of the division of a `BigRational` can always be represented exactly:

```php
echo BigRational::of('123/456')->dividedBy('7'); // 123/3192
echo BigRational::of('123/456')->dividedBy('9/8'); // 984/4104
```

### Serialization

`BigInteger`, `BigDecimal` and `BigRational` can be safely serialized on a machine and unserialized on another,
even if these machines do not share the same set of PHP extensions.

For example, serializing on a machine with GMP support and unserializing on a machine that does not have this extension
installed will still work as expected.
