<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication;

/**
 * Removes request failure_path_parameter parameter if its value is like route.
 */
class DefaultAuthenticationFailureHandler extends Authentication\DefaultAuthenticationFailureHandler
{
    use ProcessRequestParameterLikeRouteTrait;

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->processRequestParameter($request, $this->options['failure_path_parameter']);
        return parent::onAuthenticationFailure($request, $exception);
    }
}
