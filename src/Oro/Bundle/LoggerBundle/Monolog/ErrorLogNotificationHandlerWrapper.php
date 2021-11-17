<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;
use Oro\Bundle\LoggerBundle\Provider\ErrorLogNotificationRecipientsProvider;

/**
 * Prevents record handling if there are no recipients configured for an error log notification.
 */
class ErrorLogNotificationHandlerWrapper extends HandlerWrapper
{
    private ErrorLogNotificationRecipientsProvider $recipientsProvider;

    /** @var string[]|null */
    private ?array $recipients = null;

    public function __construct(
        HandlerInterface $innerHandler,
        ErrorLogNotificationRecipientsProvider $recipientsProvider
    ) {
        parent::__construct($innerHandler);

        $this->recipientsProvider = $recipientsProvider;
    }

    /**
     * Prevents record handling if there are no recipients configured for an error log notification.
     *
     * {@inheritdoc}
     */
    public function handle(array $record): void
    {
        if (!$this->getRecipientsEmailAddresses()) {
            return;
        }

        parent::handle($record);
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

        parent::handleBatch($records);
    }

    /**
     * Prevents record handling if there are no recipients configured for an error log notification.
     *
     * {@inheritdoc}
     */
    public function isHandling(array $record): bool
    {
        return parent::isHandling($record) && $this->getRecipientsEmailAddresses();
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
