<?php

namespace Oro\Bundle\ApiBundle\Config;

use Symfony\Component\Validator\Constraint;

/**
 * A set of reusable static methods to manage validation constraints in the form options.
 */
class FormConstraintUtil
{
    private const CONSTRAINTS = 'constraints';

    /**
     * Gets existing validation constraints from the form options.
     *
     * @param array|null $formOptions
     *
     * @return array|null [Constraint object or [constraint name or class => constraint options, ...], ...]
     */
    public static function getFormConstraints(?array $formOptions): ?array
    {
        return self::hasFormConstraints($formOptions)
            ? $formOptions[self::CONSTRAINTS]
            : null;
    }

    /**
     * Adds a validation constraint to the form options.
     *
     * @param array|null $formOptions
     * @param Constraint $constraint
     *
     * @return array The updated form options
     */
    public static function addFormConstraint(?array $formOptions, Constraint $constraint): array
    {
        $formOptions[self::CONSTRAINTS][] = $constraint;

        return $formOptions;
    }

    /**
     * Removes a validation constraint from the form options by its class.
     *
     * @param array|null $formOptions
     * @param string     $constraintClass
     *
     * @return array|null The updated form options
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function removeFormConstraint(?array $formOptions, string $constraintClass): ?array
    {
        if (!self::hasFormConstraints($formOptions)) {
            return $formOptions;
        }

        $resultConstraints = [];
        foreach ($formOptions[self::CONSTRAINTS] as $formConstraint) {
            if (\is_array($formConstraint)) {
                $subConstraints = [];
                foreach ($formConstraint as $className => $options) {
                    if ($className !== $constraintClass) {
                        $subConstraints[$className] = $options;
                    }
                }
                if (!empty($subConstraints)) {
                    $resultConstraints[] = $subConstraints;
                }
            } elseif ($formConstraint instanceof Constraint && !is_a($formConstraint, $constraintClass)) {
                $resultConstraints[] = $formConstraint;
            }
        }

        if ($resultConstraints) {
            $formOptions[self::CONSTRAINTS] = $resultConstraints;
        } else {
            unset($formOptions[self::CONSTRAINTS]);
        }

        if (empty($formOptions)) {
            $formOptions = null;
        }

        return $formOptions;
    }

    private static function hasFormConstraints(?array $formOptions): bool
    {
        return !empty($formOptions) && \array_key_exists(self::CONSTRAINTS, $formOptions);
    }
}
