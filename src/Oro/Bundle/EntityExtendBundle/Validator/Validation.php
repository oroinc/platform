<?php

/*
 * This file is a copy of {@see Symfony\Component\Validator\Validation}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\EntityExtendBundle\Validator;

use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilderInterface;

/**
 * @todo: This class should be removed after https://github.com/symfony/symfony/issues/18930
 */
final class Validation
{
    /**
     * The Validator API provided by Symfony 2.4 and older.
     *
     * @deprecated use API_VERSION_2_5_BC instead.
     */
    const API_VERSION_2_4 = 1;

    /**
     * The Validator API provided by Symfony 2.5 and newer.
     */
    const API_VERSION_2_5 = 2;

    /**
     * The Validator API provided by Symfony 2.5 and newer with a backwards
     * compatibility layer for 2.4 and older.
     */
    const API_VERSION_2_5_BC = 3;

    /**
     * Creates a new validator.
     *
     * If you want to configure the validator, use
     * {@link createValidatorBuilder()} instead.
     *
     * @return ValidatorInterface The new validator.
     */
    public static function createValidator()
    {
        return self::createValidatorBuilder()->getValidator();
    }

    /**
     * Creates a configurable builder for validator objects.
     *
     * @return ValidatorBuilderInterface The new builder.
     */
    public static function createValidatorBuilder()
    {
        return new ValidatorBuilder();
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
