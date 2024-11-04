<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Define some ExpressionLanguage functions.
 *
 * To get a parameter, use parameter_or_null('kernel.debug'). If parameter does not exist null will be returned.
 */
class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'parameter_or_null',
                function ($arg) {
                    return sprintf('$container->hasParameter(%1$s) ? $container->getParameter(%1$s) : null', $arg);
                },
                function (array $variables, $value) {
                    $container = $variables['container'];
                    if (!$container->hasParameter($value)) {
                        return null;
                    }

                    return $variables['container']->getParameter($value);
                }
            ),
        ];
    }
}
