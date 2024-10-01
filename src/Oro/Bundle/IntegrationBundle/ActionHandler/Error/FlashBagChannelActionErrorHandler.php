<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler\Error;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handle flash bag channel actions.
 */
class FlashBagChannelActionErrorHandler implements ChannelActionErrorHandlerInterface
{
    public function __construct(protected RequestStack $requestStack)
    {
    }

    #[\Override]
    public function handleErrors($errors)
    {
        $flashBag = $this->requestStack->getSession()->getFlashBag();

        foreach ($errors as $error) {
            $flashBag->add('error', $error);
        }
    }
}
