<?php

namespace Oro\Bundle\NavigationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for checking max nested level.
 */
class MaxNestedLevel extends Constraint
{
    public const MAX_NESTING_LEVEL_ERROR = 'd8628c8c-8a9f-4800-97ea-16ee0a109388';

    protected static $errorNames = [
        self::MAX_NESTING_LEVEL_ERROR => 'MAX_NESTING_LEVEL_ERROR',
    ];

    public string $message = 'oro.navigation.validator.menu_update.max_nested_level.message';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
