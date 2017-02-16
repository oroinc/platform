<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler\Error;

use Symfony\Component\HttpFoundation\Session\Session;

class ChannelActionErrorHandler implements ChannelActionErrorHandlerInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param string[] $errors
     */
    public function handleErrors($errors)
    {
        $flashBag = $this->session->getFlashBag();

        foreach ($errors as $error) {
            $flashBag->add('error', $error);
        }
    }
}
