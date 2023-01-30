<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Handler\FingersCrossedHandler;

/**
 * Provides a possibility to change the logging level for a certain period of time
 * and bypass the standard FingersCrossedHandler::handle.
 * Decoration is not an option because we have to call the nested handler based on conditions.
 */
class ConfigurableFingersCrossedHandler extends FingersCrossedHandler
{
    private ?LogLevelConfig $config = null;

    /**
     * Using setter instead of a constructor argument to not rely on a parent handler constructor signature
     */
    public function setLogLevelConfig(LogLevelConfig $config)
    {
        $this->config = $config;
    }

    public function handle(array $record): bool
    {
        if (!$this->config?->isActive()) {
            return parent::handle($record);
        }
        if ($record['level'] >= $this->config->getMinLevel()) {
            if ($this->processors) {
                foreach ($this->processors as $processor) {
                    $record = call_user_func($processor, $record);
                }
            }
            $this->getHandler($record)->handle($record);
        }

        return false === $this->bubble;
    }

    public function reset()
    {
        $this->config->reset();
        parent::reset();
    }
}
