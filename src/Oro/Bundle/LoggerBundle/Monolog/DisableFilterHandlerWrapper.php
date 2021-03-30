<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Handler\FilterHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;
use Monolog\Logger;

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

    /**
     * @var HandlerInterface
     */
    protected $nestedHandler;

    /**
     * @var LogLevelConfig
     */
    private $logLevelConfig;

    /**
     * @var bool
     */
    private $filterHandlerIsDisabled = false;

    /**
     * {@inheritDoc}
     * @param LogLevelConfig $config
     */
    public function __construct(LogLevelConfig $config, FilterHandler $handler)
    {
        parent::__construct($handler);
        $this->setNestedHandler($handler);
        $this->logLevelConfig = $config;
    }

    public function handle(array $record)
    {
        $this->setMinLevel();

        return parent::handle($record);
    }

    public function handleBatch(array $records)
    {
        $this->setMinLevel();

        return parent::handleBatch($records);
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
        if (\method_exists($this->nestedHandler, 'reset')) {
            $this->nestedHandler->reset();
        }
    }

    private function setNestedHandler(FilterHandler $handler)
    {
        $reflectionProperty = new \ReflectionProperty($handler, 'handler');
        $reflectionProperty->setAccessible(true);
        $this->nestedHandler = $reflectionProperty->getValue($handler);
    }
}
