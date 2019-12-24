<?php

namespace Oro\Bundle\LayoutBundle\Layout\ExpressionLanguageProvider;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Provides expression language function to check that variable is an instantiated object of a certain class or
 * has class in variable as one of its parents.
 */
class InstanceofExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'is_a',
                static function ($object, $className) {
                    return sprintf("is_a('%s','%s')", $object, $className);
                },
                static function ($arguments, $object, $className) {
                    return is_a($object, $className, true);
                }
            ),
        ];
    }
}
