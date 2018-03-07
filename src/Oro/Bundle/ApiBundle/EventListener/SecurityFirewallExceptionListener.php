<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prevents usage of Session in case it the request does not have session identifier in cookies.
 * This is required because API can work in two modes, stateless and statefull.
 * The statefull mode is used when API is called internally from web pages as AJAX request.
 */
class SecurityFirewallExceptionListener extends ExceptionListener
{
    /** @var string */
    private $sessionName;

    /**
     * @param string $sessionName
     */
    public function setSessionName(string $sessionName): void
    {
        $this->sessionName = $sessionName;
    }

    /**
     * {@inheritdoc}
     */
    protected function setTargetPath(Request $request): void
    {
        if ($request->cookies->has($this->sessionName)) {
            parent::setTargetPath($request);
        }
    }
}
