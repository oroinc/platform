<?php

namespace Oro\Bundle\PlatformBundle\Validator\Constraints;

// phpcs:disable Generic.Files.LineLength.TooLong
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Exception\LogicException;

/**
 * Conditional validation constraint.
 *
 * This file is a copy of 6.4 version of {@see \Symfony\Component\Validator\Constraints\When}
 *
 *  Copyright (c) 2004-present Fabien Potencier
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the 'Software'), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "METHOD", "ANNOTATION"})
 */
class When extends Composite
{
    public $expression;
    public $constraints = [];
    public $values = [];

    public function __construct(string|Expression|array $expression, array|Constraint|null $constraints = null, ?array $values = null, ?array $groups = null, $payload = null, array $options = [])
    {
        if (!class_exists(ExpressionLanguage::class)) {
            throw new LogicException(sprintf('The "symfony/expression-language" component is required to use the "%s" constraint. Try running "composer require symfony/expression-language".', __CLASS__));
        }

        if (\is_array($expression)) {
            $options = array_merge($expression, $options);
        } else {
            $options['expression'] = $expression;
            $options['constraints'] = $constraints;
        }

        if (isset($options['constraints']) && !\is_array($options['constraints'])) {
            $options['constraints'] = [$options['constraints']];
        }

        if (null !== $groups) {
            $options['groups'] = $groups;
        }

        if (null !== $payload) {
            $options['payload'] = $payload;
        }

        parent::__construct($options);

        $this->values = $values ?? $this->values;
    }

    public function getRequiredOptions(): array
    {
        return ['expression', 'constraints'];
    }

    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }

    protected function getCompositeOption(): string
    {
        return 'constraints';
    }
}
