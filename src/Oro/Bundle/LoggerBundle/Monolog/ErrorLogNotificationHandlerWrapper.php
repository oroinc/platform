<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;
use Oro\Bundle\LoggerBundle\Provider\ErrorLogNotificationRecipientsProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * Prevents record handling if there are no recipients configured for an error log notification.
 */
class ErrorLogNotificationHandlerWrapper extends HandlerWrapper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ErrorLogNotificationRecipientsProvider $recipientsProvider;

    /** @var string[]|null */
    private ?array $recipients = null;

    private bool $preventHandling = false;

    public function __construct(
        HandlerInterface $innerHandler,
        ErrorLogNotificationRecipientsProvider $recipientsProvider
    ) {
        parent::__construct($innerHandler);

        $this->recipientsProvider = $recipientsProvider;
        $this->logger = new NullLogger();
    }

    /**
     * Prevents record handling if there are no recipients configured for an error log notification.
     *
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        if (!$this->getRecipientsEmailAddresses()) {
            return false;
        }

        try {
            $result = parent::handle($record);
        } catch (InvalidArgumentException | RfcComplianceException $exception) {
            // These exceptions can be thrown in case the sender or recipients emails are invalid and cannot be used
            // to send an error log email notification.
            // Flag $preventHandling is set to true to prevent further tries of logging.
            $result = false;
            $this->preventHandling = true;

            $this->logFailedToSend($exception, ['record' => $record]);
        }

        return $result;
    }

    /**
     * Prevents records handling if there are no recipients configured for an error log notification.
     *
     * {@inheritdoc}
     */
    public function handleBatch(array $records): void
    {
        if (!$this->getRecipientsEmailAddresses()) {
            return;
        }

        try {
            parent::handleBatch($records);
        } catch (InvalidArgumentException | RfcComplianceException $exception) {
            // These exceptions can be thrown in case the sender or recipients emails are invalid and cannot be used
            // to send an error log email notification.
            // Flag $preventHandling is set to true to prevent further tries of logging.
            $this->preventHandling = true;

            $this->logFailedToSend($exception, ['records' => $records]);
        }
    }

    private function logFailedToSend(\Throwable $throwable, array $context): void
    {
        $this->logger->warning(
            sprintf('Failed to send error log email notification: %s', $throwable->getMessage()),
            $context + ['throwable' => $throwable]
        );
    }

    /**
     * Prevents record handling if there are no recipients configured for an error log notification.
     *
     * {@inheritdoc}
     */
    public function isHandling(array $record): bool
    {
        return !$this->preventHandling && parent::isHandling($record) && $this->getRecipientsEmailAddresses();
    }

    /**
     * Returns recipients email addresses, uses local caching.
     *
     * @return string[]
     */
    private function getRecipientsEmailAddresses(): array
    {
        if ($this->recipients === null) {
            $this->recipients = $this->recipientsProvider->getRecipientsEmailAddresses();
        }

        return $this->recipients;
    }
}
