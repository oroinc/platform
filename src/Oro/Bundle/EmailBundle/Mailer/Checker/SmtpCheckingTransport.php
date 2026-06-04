<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Oro\Bundle\ConfigBundle\Validator\OutboundConnectionValidatorInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

/**
 * Extends {@see EsmtpTransport} to add ability to check if connection could be established.
 *
 * @internal
 */
class SmtpCheckingTransport extends EsmtpTransport
{
    private ?int $connectionCheckDuration = null;
    private ?OutboundConnectionValidatorInterface $connectionValidator = null;

    /**
     * Gets the duration, in seconds, used to check whether an SMTP connection can be established.
     * It is used to prevent time-based attacks by implementing an artificial delay
     * so that both successful and failed requests take the same amount of time.
     * null indicates that the constant-time connection check is disabled.
     */
    public function getConnectionCheckDuration(): ?int
    {
        return $this->connectionCheckDuration;
    }

    /**
     * Sets the duration, in seconds, used to check whether an SMTP connection can be established.
     * It is used to prevent time-based attacks by implementing an artificial delay
     * so that both successful and failed requests take the same amount of time.
     * Set to null or 0 to disable the constant-time connection check.
     */
    public function setConnectionCheckDuration(?int $durationInSeconds): void
    {
        if (null !== $durationInSeconds && $durationInSeconds < 0) {
            throw new \InvalidArgumentException('The duration cannot be a negative number.');
        }
        $this->connectionCheckDuration = $durationInSeconds > 0 ? $durationInSeconds : null;
    }

    /**
     * Sets a validator for outbound connections when it is necessary to check
     * whether outbound connections to external hosts and ports are permitted.
     */
    public function setConnectionValidator(?OutboundConnectionValidatorInterface $connectionValidator): void
    {
        $this->connectionValidator = $connectionValidator;
    }

    public function check(?string &$error = null): bool
    {
        if (null === $this->connectionCheckDuration) {
            return $this->checkConnection($error);
        }

        $stream = $this->getStream();
        if (!$stream instanceof SocketStream) {
            return $this->checkConnection($error);
        }

        $randomFactor = BigDecimal::of(random_int(95, 105))->dividedBy(100, 2, RoundingMode::HALF_UP);
        $duration = BigDecimal::of($this->connectionCheckDuration)
            ->multipliedBy($randomFactor)
            ->toFloat();
        $startTime = microtime(true);
        $initialTimeout = $stream->getTimeout();
        $stream->setTimeout((float)$this->connectionCheckDuration);
        try {
            $isSuccessful = $this->checkConnection($error);
        } finally {
            $stream->setTimeout($initialTimeout);
        }
        $elapsed = microtime(true) - $startTime;
        if ($elapsed < $duration) {
            $sleepDuration = BigDecimal::of($duration)
                ->minus(BigDecimal::of($elapsed))
                ->multipliedBy(1000000)
                ->toScale(0, RoundingMode::DOWN)
                ->toInt();
            usleep($sleepDuration);
        }

        return $isSuccessful;
    }

    private function checkConnection(?string &$error = null): bool
    {
        $logContext = $this->getLogContext();
        $this->getLogger()->info('Checking SMTP connection ...', $logContext);
        if (!$this->isConnectionAllowed()) {
            $this->getLogger()->error('SMTP connections to this host and port are not allowed.', $logContext);
            $error = 'A connection could not be established';

            return false;
        }

        $isSuccessful = true;
        try {
            $this->getStream()->initialize();
            // Read the opening SMTP greeting
            $this->executeCommand('', [220]);
            $this->doHeloCommand();
            $this->getLogger()->info('SMTP connection was successfully established.', $logContext);
        } catch (\RuntimeException $e) {
            $isSuccessful = false;
            $this->getLogger()->error(
                'Could not establish SMTP connection.',
                array_merge($logContext, ['exception' => $e])
            );
            $error = 'A connection could not be established';
        } finally {
            $this->getStream()->terminate();
        }

        return $isSuccessful;
    }

    private function isConnectionAllowed(): bool
    {
        if (null === $this->connectionValidator) {
            return true;
        }

        $stream = $this->getStream();
        if (!$stream instanceof SocketStream) {
            return true;
        }

        return $this->connectionValidator->isConnectionAllowed($stream->getHost(), $stream->getPort());
    }

    private function getLogContext(): array
    {
        $context = [];
        $stream = $this->getStream();
        if ($stream instanceof SocketStream) {
            $context['host'] = $stream->getHost();
            $context['port'] = $stream->getPort();
        }

        return $context;
    }
}
