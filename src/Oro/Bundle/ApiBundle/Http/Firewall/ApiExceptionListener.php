<?php

namespace Oro\Bundle\ApiBundle\Http\Firewall;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener;

class ApiExceptionListener extends ExceptionListener
{
    /** @var array */
    protected $sessionOptions;

    /**
     * @param array $sessionOptions
     */
    public function setSessionOptions(array $sessionOptions)
    {
        $this->sessionOptions = $sessionOptions;
    }

    /**
     * {@inheritdoc}
     */
    protected function setTargetPath(Request $request)
    {
        if ($request->cookies->has($this->sessionOptions['name'])) {
            parent::setTargetPath($request);
        }
    }
}
