<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler\Error;

use Symfony\Component\HttpFoundation\Session\Session;

class FlashBagChannelActionErrorHandler implements ChannelActionErrorHandlerInterface
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
     * {@inheritdoc}
     */
    public function handleErrors($errors)
    {
        $flashBag = $this->session->getFlashBag();

        foreach ($errors as $error) {
            $flashBag->add('error', $error);
        }
    }
}
