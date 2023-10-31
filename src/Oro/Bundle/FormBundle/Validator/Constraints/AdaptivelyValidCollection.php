<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for validating a collection with different validation groups for the new, updated and
 * unchanged elements.
 */
class AdaptivelyValidCollection extends Constraint
{
    /**
     * @var array|string[] Validation groups to apply to a new element.
     */
    public array $validationGroupsForNew = [];

    /**
     * @var array|string[] Validation groups to apply to an updated element.
     */
    public array $validationGroupsForUpdated = [];

    /**
     * @var array|string[] Validation groups to apply to an unchanged element.
     */
    public array $validationGroupsForUnchanged = [];

    /**
     * @var array<string> Entity fields to check for changes to decide if an element is updated or unchanged.
     */
    public array $trackFields = [];
}
