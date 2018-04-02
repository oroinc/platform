<?php

namespace Oro\Bundle\EmailBundle\Event;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Symfony\Component\EventDispatcher\Event;

class SendEmailTransport extends Event
{
    const NAME = 'oro_email.send_email_transport';

    /** @var EmailOrigin */
    protected $emailOrigin;

    /** @var \Swift_Transport_EsmtpTransport */
    protected $transport;

    /**
     * @param EmailOrigin $emailOrigin
     * @param \Swift_Transport_EsmtpTransport|null $transport
     */
    public function __construct($emailOrigin, $transport)
    {
        $this->emailOrigin = $emailOrigin;
        $this->transport = $transport;
    }

    /**
     * @return EmailOrigin
     */
    public function getEmailOrigin()
    {
        return $this->emailOrigin;
    }

    /**
     * @return \Swift_Transport_EsmtpTransport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param \Swift_Transport_EsmtpTransport|null $transport
     */
    public function setTransport($transport)
    {
        $this->transport = $transport;
    }
}
