<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Handler\FilterHandler;
use Monolog\Handler\HandlerWrapper;
use Monolog\Logger;
use Monolog\ResettableInterface;

/**
 * Disables Monolog filter handler filtering when detailed logs are active,
 * and resets nested handler on a filter handler reset call.
 */
class DisableFilterHandlerWrapper extends HandlerWrapper
{
    /**
     * @var FilterHandler
     */
    protected $handler;

    private LogLevelConfig $logLevelConfig;

    private bool $filterHandlerIsDisabled = false;

    /**
     * {@inheritDoc}
     */
    public function __construct(LogLevelConfig $config, FilterHandler $handler)
    {
        parent::__construct($handler);
        $this->logLevelConfig = $config;
    }

    public function handle(array $record): bool
    {
        $this->setMinLevel();

        return parent::handle($record);
    }

    public function handleBatch(array $records): void
    {
        $this->setMinLevel();

        parent::handleBatch($records);
    }

    /**
     * @return array
     */
    public function getAcceptedLevels()
    {
        return $this->handler->getAcceptedLevels();
    }

    /**
     * @param int|string|array $minLevelOrList A list of levels to accept or a minimum level or level name if maxLevel
     *                                         is provided
     * @param int|string       $maxLevel       Maximum level or level name to accept, only used if $minLevelOrList is
     *                                         not an array
     */
    public function setAcceptedLevels($minLevelOrList = Logger::DEBUG, $maxLevel = Logger::EMERGENCY)
    {
        $this->handler->setAcceptedLevels($minLevelOrList, $maxLevel);
    }

    private function setMinLevel(): void
    {
        if (!$this->filterHandlerIsDisabled && $this->logLevelConfig->isActive()) {
            // log all the levels
            $this->handler->setAcceptedLevels();
            $this->filterHandlerIsDisabled = true;
        }
    }

    public function reset()
    {
        $nestedHandler = $this->handler->getHandler();
        if ($nestedHandler instanceof ResettableInterface) {
            $nestedHandler->reset();
        }

        return parent::reset();
    }
}
