<?php

namespace Oro\Bundle\EmailBundle\Mailer;

use Doctrine\Bundle\DoctrineBundle\Registry;

class MailboxMailerManager
{
    /** @var Registry */
    private $doctrine;
    /** @var \Swift_Mailer[] */
    protected $mailers = [];

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getMailerForAddress($address)
    {
        if (!isset($this->mailers[$address])) {
            return $this->mailers[$address] = $this->createMailerForMailboxAddress($address);
        }

        return $this->mailers[$address];
    }

    protected function createMailerForMailboxAddress($address)
    {
        $mailbox = $this->findMailboxByAddress($address);

        if ($mailbox === null) {
            return null;
        }

        $smtp = (array)$mailbox->getSmtpSettings();

        if (!isset($smtp['enabled']) || !$smtp['enabled']) {
            return null;
        }

        $transport = $this->getTransportFromSmtpSettings($smtp);

        return \Swift_Mailer::newInstance($transport);
    }

    protected function getTransportFromSmtpSettings($smtp)
    {
        $transport = new \Swift_SmtpTransport();

        if (isset($smtp['host'])) {
            $transport->setHost($smtp['host']);
        }
        if (isset($smtp['port'])) {
            $transport->setPort($smtp['port']);
        }
        if (isset($smtp['encryption'])) {
            $transport->setEncryption($smtp['encryption']);
        }
        if (isset($smtp['username'])) {
            $transport->setUsername($smtp['username']);
        }
        if (isset($smtp['password'])) {
            $transport->setPassword($smtp['password']);
        }

        return $transport;
    }

    protected function findMailboxByAddress($address)
    {
        return $this->doctrine->getRepository('OroEmailBundle:Mailbox')->findOneByEmail($address);
    }
}
