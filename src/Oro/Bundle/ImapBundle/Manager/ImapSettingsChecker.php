<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Oro\Bundle\ConfigBundle\Validator\OutboundConnectionValidatorInterface;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;

/**
 * Checks that an IMAP connection can be established with provided parameters
 */
class ImapSettingsChecker
{
    /** @var ImapConnectorFactory */
    private $connectorFactory;

    /** @var SymmetricCrypterInterface */
    private $encryptor;

    /** @var LoggerInterface */
    private $logger;

    /** @var int|null */
    private $connectionCheckDuration;

    /** @var OutboundConnectionValidatorInterface|null */
    private $connectionValidator;

    public function __construct(
        ImapConnectorFactory $connectorFactory,
        SymmetricCrypterInterface $encryptor
    ) {
        $this->connectorFactory = $connectorFactory;
        $this->encryptor = $encryptor;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Gets the duration, in seconds, used to check whether an IMAP connection can be established.
     * It is used to prevent time-based attacks by implementing an artificial delay
     * so that both successful and failed requests take the same amount of time.
     * null indicates that the constant-time connection check is disabled.
     */
    public function getConnectionCheckDuration(): ?int
    {
        return $this->connectionCheckDuration;
    }

    /**
     * Sets the duration, in seconds, used to check whether an IMAP connection can be established.
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

    /**
     * @param UserEmailOrigin $value
     *
     * @return bool
     */
    public function checkConnection(UserEmailOrigin $value)
    {
        if (null === $this->connectionCheckDuration) {
            return $this->doCheckConnection($value);
        }

        $randomFactor = BigDecimal::of(random_int(95, 105))->dividedBy(100, 2, RoundingMode::HALF_UP);
        $duration = BigDecimal::of($this->connectionCheckDuration)
            ->multipliedBy($randomFactor)
            ->toFloat();
        $startTime = microtime(true);
        $isSuccessful = $this->doCheckConnection($value, $this->connectionCheckDuration);
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

    private function doCheckConnection(UserEmailOrigin $value, ?int $connectionTimeout = null): bool
    {
        $logContext = ['host' => $value->getImapPort(), 'port' => $value->getImapPort()];
        $this->logger->info('Checking IMAP connection ...', $logContext);
        if (!$this->isConnectionAllowed($value)) {
            $this->logger->error('IMAP connections to this host and port are not allowed.', $logContext);

            return false;
        }

        $isSuccessful = true;
        try {
            $config = new ImapConfig(
                $value->getImapHost(),
                $value->getImapPort(),
                $value->getImapEncryption(),
                $value->getUser(),
                $this->encryptor->decryptData($value->getPassword())
            );
            $config->setConnectionTimeout($connectionTimeout);
            $connector = $this->connectorFactory->createImapConnector($config);
            $connector->getCapability();
            $this->logger->info('IMAP connection was successfully established.', $logContext);
        } catch (\Exception $e) {
            $isSuccessful = false;
            $this->logger->error(
                'Could not establish IMAP connection.',
                array_merge($logContext, ['exception' => $e])
            );
        }

        return $isSuccessful;
    }

    private function isConnectionAllowed(UserEmailOrigin $value): bool
    {
        if (null === $this->connectionValidator) {
            return true;
        }

        return $this->connectionValidator->isConnectionAllowed($value->getImapHost(), $value->getImapPort());
    }
}
