<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Symfony\Component\Mailer\Transport\Dsn;

/**
 * Checks connection for "smtp://" or "smtps://" DSN.
 */
class SmtpConnectionChecker implements ConnectionCheckerInterface
{
    public function supports(Dsn $dsn): bool
    {
        return in_array($dsn->getScheme(), ['smtp', 'smtps']);
    }

    /**
     * {@inheritdoc}
     */
    public function checkConnection(Dsn $dsn, string &$error = null): bool
    {
        return $this->createSmtpCheckingTransport($dsn)->check($error);
    }

    private function createSmtpCheckingTransport(Dsn $dsn): SmtpCheckingTransport
    {
        $tls = $dsn->getScheme() === 'smtps' ? true : null;
        $port = $dsn->getPort(0);
        $host = $dsn->getHost();

        $transport = new SmtpCheckingTransport($host, $port, $tls, null, null);
        if ($dsn->getUser()) {
            $transport->setUsername($dsn->getUser());
        }

        if ($dsn->getPassword()) {
            $transport->setPassword($dsn->getPassword());
        }

        return $transport;
    }
}
