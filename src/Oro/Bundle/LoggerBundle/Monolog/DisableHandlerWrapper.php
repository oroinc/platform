<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;

/**
 * Disables Monolog handler when detailed logs are active.
 * E.g. mailer handler must be disabled to not send emails on each request.
 */
class DisableHandlerWrapper extends HandlerWrapper
{
    private LogLevelConfig $logLevelConfig;

    /**
     * {@inheritDoc}
     */
    public function __construct(LogLevelConfig $config, HandlerInterface $handler)
    {
        parent::__construct($handler);
        $this->logLevelConfig = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function isHandling(array $record): bool
    {
        return !$this->logLevelConfig->isActive();
    }

    /**
     * {@inheritdoc}
     */
    public function handleBatch(array $records): void
    {
        if ($this->logLevelConfig->isActive()) {
            return;
        }

        parent::handleBatch($records);
    }

    /**
     * As deactivated handlers may have extra public methods, we need to process them as well.
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func([$this->handler, $name], ...$arguments);
    }
}
