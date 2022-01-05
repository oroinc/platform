<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * The storage for processors for all public API actions.
 */
class ActionProcessorBag implements ActionProcessorBagInterface
{
    /** @var ActionProcessorInterface[] [action => processor, ...] */
    private array $processors = [];

    /** @var string[]|null */
    private ?array $publicActions = null;

    /**
     * {@inheritdoc}
     */
    public function addProcessor(ActionProcessorInterface $processor): void
    {
        $this->processors[$processor->getAction()] = $processor;
        $this->publicActions = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor(string $action): ActionProcessorInterface
    {
        if (!isset($this->processors[$action])) {
            throw new \InvalidArgumentException(sprintf('A processor for "%s" action was not found.', $action));
        }

        return $this->processors[$action];
    }

    /**
     * {@inheritdoc}
     */
    public function getActions(): array
    {
        if (null === $this->publicActions) {
            $publicActions = array_keys($this->processors);
            /**
             * The "unhandled_error" action is a special case.
             * This action is not a public action, but it is stored in this bag to be able get it
             * by RequestActionHandler.
             * @see \Oro\Bundle\ApiBundle\Request\RequestActionHandler::handleUnhandledError
             */
            $key = array_search('unhandled_error', $publicActions, true);
            if (false !== $key) {
                unset($publicActions[$key]);
                $publicActions = array_values($publicActions);
            }
            $this->publicActions = $publicActions;
        }

        return $this->publicActions;
    }
}
