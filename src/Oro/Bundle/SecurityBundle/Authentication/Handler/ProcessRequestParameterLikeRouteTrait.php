<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\ParameterBagUtils;

/**
 * Removes request parameter if its value is like route name.
 */
trait ProcessRequestParameterLikeRouteTrait
{
    protected function processRequestParameter(Request $request, string $parameter): void
    {
        $parameterValue = ParameterBagUtils::getRequestParameterValue($request, $parameter);

        //value starts with words separated by underscore
        if (preg_match('/^(\w+_\w)/', $parameterValue)) {
            $request->request->remove($parameter);
            $request->query->remove($parameter);

            $this->logger?->debug("Request parameter is removed because it looks like route name.", [
                'parameter' => $parameter,
                'value' => $parameterValue,
            ]);
        }
    }
}
