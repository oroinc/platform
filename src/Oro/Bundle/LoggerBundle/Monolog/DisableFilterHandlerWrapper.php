<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\ResettableInterface;

/**
 * Disables Monolog filter handler filtering when detailed logs are active,
 * and resets nested handler on a filter handler reset call.
 */
class DisableFilterHandlerWrapper extends HandlerWrapper
{
    private bool $filterHandlerIsDisabled = false;

    public function __construct(protected LogLevelConfig $logLevelConfig, protected HandlerInterface $innerHandler)
    {
        parent::__construct($innerHandler);
    }

    #[\Override]
    public function handle(LogRecord $record): bool
    {
        $this->setMinLevel();

        return parent::handle($record);
    }

    #[\Override]
    public function handleBatch(array $records): void
    {
        $this->setMinLevel();

        parent::handleBatch($records);
    }

    public function getAcceptedLevels(): array
    {
        return $this->innerHandler->getAcceptedLevels();
    }

    /**
     * @param int|string|array $minLevelOrList A list of levels to accept or a minimum level or level name if maxLevel
     *                                         is provided
     * @param int|string       $maxLevel       Maximum level or level name to accept, only used if $minLevelOrList is
     *                                         not an array
     */
    public function setAcceptedLevels($minLevelOrList = Logger::DEBUG, $maxLevel = Logger::EMERGENCY): void
    {
        $this->innerHandler->setAcceptedLevels($minLevelOrList, $maxLevel);
    }

    private function setMinLevel(): void
    {
        if (!$this->filterHandlerIsDisabled && $this->logLevelConfig->isActive()) {
            // log all the levels
            $this->innerHandler->setAcceptedLevels();
            $this->filterHandlerIsDisabled = true;
        }
    }

    #[\Override]
    public function reset(): void
    {
        $nestedHandler = $this->innerHandler->getHandler();
        if ($nestedHandler instanceof ResettableInterface) {
            $nestedHandler->reset();
        }

        parent::reset();
    }
}
