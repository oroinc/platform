<?php

namespace Oro\Bundle\ApiBundle\Security\Http\Firewall;

use Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener as BaseExceptionListener;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prevents usage of Session in case the request does not have session identifier in cookies.
 * It is required because API can work in two modes, stateless and statefull.
 * The statefull mode is used when API is called internally from web pages as AJAX request.
 */
class ExceptionListener extends BaseExceptionListener
{
    /**
     * {@inheritdoc}
     */
    protected function setTargetPath(Request $request): void
    {
        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session && $request->cookies->has($session->getName())) {
            parent::setTargetPath($request);
        }
    }
}
