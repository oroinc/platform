<?php

namespace Oro\Component\Action\Action;

use Psr\Log\LoggerInterface;

/**
 * Executes a sequence of actions with optional error handling and logging.
 *
 * This action manages the execution of multiple child actions in sequence. Each action can be
 * configured to either break execution on failure or continue with optional error logging.
 * Useful for complex workflows that require multiple steps with conditional error handling.
 */
class TreeExecutor extends AbstractAction
{
    public const ALIAS = 'tree';

    /**
     * @var array
     */
    protected $actions = array();

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $logLevel;

    /**
     * @param LoggerInterface|null $logger
     * @param string $logLevel
     */
    public function __construct(?LoggerInterface $logger = null, $logLevel = 'ALERT')
    {
        $this->logger = $logger;
        $this->logLevel = $logLevel;
    }

    /**
     * @param ActionInterface $action
     * @param bool $breakOnFailure
     * @return TreeExecutor
     */
    public function addAction(ActionInterface $action, $breakOnFailure = true)
    {
        $this->actions[] = array(
            'instance' => $action,
            'breakOnFailure' => $breakOnFailure
        );

        return $this;
    }

    #[\Override]
    protected function executeAction($context)
    {
        foreach ($this->actions as $actionConfig) {
            try {
                /** @var ActionInterface $action */
                $action = $actionConfig['instance'];
                $action->execute($context);
            } catch (\Exception $e) {
                if ($actionConfig['breakOnFailure']) {
                    throw $e;
                } elseif (null !== $this->logger) {
                    $this->logger->log($this->logLevel, $e->getMessage());
                }
            }
        }
    }

    #[\Override]
    public function initialize(array $options)
    {
        return $this;
    }
}
