<?php

namespace Oro\Bundle\SoapBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Reverts https://github.com/symfony/symfony/pull/28565 for REST API sub-requests to avoid BC break.
 */
class ApiSubRequestListener
{
    /** @var array */
    private $rules;

    /**
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ($event->getRequestType() !== HttpKernelInterface::SUB_REQUEST || !$request->getRequestFormat(null)) {
            return;
        }

        foreach ($this->rules as $rule) {
            if (!$rule['stop'] && preg_match('#' . $rule['path'] . '/.+#', $request->getRequestUri())) {
                $request->setRequestFormat(null);
                break;
            }
        }
    }
}
