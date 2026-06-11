<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Oro\Bundle\ConfigBundle\Validator\OutboundConnectionValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * Checks connection for "smtp://" or "smtps://" DSN.
 */
class SmtpConnectionChecker implements ConnectionCheckerInterface
{
    private ?int $connectionCheckDuration = null;
    private ?OutboundConnectionValidatorInterface $connectionValidator = null;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Sets the duration, in seconds, used to check whether an SMTP connection can be established.
     * It is used to prevent time-based attacks by implementing an artificial delay
     * so that both successful and failed requests take the same amount of time.
     * Set to null or 0 to disable the constant-time connection check.
     */
    public function setConnectionCheckDuration(?int $durationInSeconds): void
    {
        $this->connectionCheckDuration = $durationInSeconds;
    }

    /**
     * Sets a validator for outbound connections when it is necessary to check
     * whether outbound connections to external hosts and ports are permitted.
     */
    public function setConnectionValidator(?OutboundConnectionValidatorInterface $connectionValidator): void
    {
        $this->connectionValidator = $connectionValidator;
    }

    #[\Override]
    public function supports(Dsn $dsn): bool
    {
        return \in_array($dsn->getScheme(), ['smtp', 'smtps']);
    }

    #[\Override]
    public function checkConnection(Dsn $dsn): bool
    {
        return $this->createSmtpCheckingTransport($dsn)->check();
    }

    private function createSmtpCheckingTransport(Dsn $dsn): SmtpCheckingTransport
    {
        $transport = new SmtpCheckingTransport(
            $dsn->getHost(),
            $dsn->getPort(0),
            $dsn->getScheme() === 'smtps' ? true : null,
            null,
            $this->logger
        );
        $transport->setConnectionCheckDuration($this->connectionCheckDuration);
        $transport->setConnectionValidator($this->connectionValidator);
        if ($dsn->getUser()) {
            $transport->setUsername($dsn->getUser());
        }
        if ($dsn->getPassword()) {
            $transport->setPassword($dsn->getPassword());
        }

        return $transport;
    }
}
