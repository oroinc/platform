<?php

namespace Oro\Bundle\PlatformBundle\Validator\Constraints;

// phpcs:disable Generic.Files.LineLength.TooLong
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Conditional validation constraint validator.
 *
 * This file is a copy of 6.4 version of {@see \Symfony\Component\Validator\Constraints\WhenValidator}
 *
 * Copyright (c) 2004-present Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the 'Software'), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */
final class WhenValidator extends ConstraintValidator
{
    public function __construct(private ?ExpressionLanguage $expressionLanguage = null)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof When) {
            throw new UnexpectedTypeException($constraint, When::class);
        }

        $context = $this->context;
        $variables = $constraint->values;
        $variables['value'] = $value;
        $variables['this'] = $context->getObject();

        if ($this->getExpressionLanguage()->evaluate($constraint->expression, $variables)) {
            $context->getValidator()->inContext($context)
                ->validate($value, $constraint->constraints);
        }
    }

    private function getExpressionLanguage(): ExpressionLanguage
    {
        if (!class_exists(ExpressionLanguage::class)) {
            throw new LogicException(sprintf('The "symfony/expression-language" component is required to use the "%s" validator. Try running "composer require symfony/expression-language".', __CLASS__));
        }

        return $this->expressionLanguage ??= new ExpressionLanguage();
    }
}
