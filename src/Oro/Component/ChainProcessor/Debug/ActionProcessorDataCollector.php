<?php

namespace Oro\Component\ChainProcessor\Debug;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collects executed actions and processors
 */
class ActionProcessorDataCollector extends DataCollector
{
    private TraceLogger $logger;
    private ?float $totalTime = null;

    public function __construct(TraceLogger $logger)
    {
        $this->logger = $logger;
        $this->reset();
    }

    /**
     * {@inheritDoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $this->data['name'] = $this->logger->getSectionName();
        $this->data['actions'] = $this->logger->getActions();
        $this->data['applicableCheckers'] = $this->logger->getApplicableCheckers();
    }

    /**
     * Whether at least one processor was executed.
     */
    public function isEmpty(): bool
    {
        return empty($this->data['actions']);
    }

    /**
     * Gets executed actions and processors as a tree.
     */
    public function getActionTree(): array
    {
        return $this->data['actions'];
    }

    /**
     * Gets executed actions.
     */
    public function getActions(): array
    {
        $actions = [];
        foreach ($this->data['actions'] as $action) {
            $name = $action['name'];
            $time = $action['time'] ?? 0;
            if (isset($actions[$name])) {
                $actions[$name]['count'] += 1;
                $actions[$name]['time'] += $time;
            } else {
                $actions[$name] = ['name' => $name, 'count' => 1, 'time' => $time];
            }
        }

        return array_values($actions);
    }

    /**
     * Gets the number of executed actions.
     */
    public function getActionCount(): int
    {
        return \count($this->data['actions']);
    }

    /**
     * Gets executed processors.
     */
    public function getProcessors(): array
    {
        $processors = [];
        foreach ($this->data['actions'] as $action) {
            if (isset($action['processors'])) {
                foreach ($action['processors'] as $processor) {
                    $id = $processor['id'];
                    $time = $processor['time'] ?? 0;
                    if (isset($processors[$id])) {
                        $processors[$id]['count'] += 1;
                        $processors[$id]['time'] += $time;
                    } else {
                        $processors[$id] = ['id' => $id, 'count' => 1, 'time' => $time];
                    }
                }
            }
        }

        return array_values($processors);
    }

    /**
     * Gets the number of executed processors.
     */
    public function getProcessorCount(): int
    {
        $count = 0;
        foreach ($this->data['actions'] as $action) {
            if (isset($action['processors'])) {
                $count += \count($action['processors']);
            }
        }

        return $count;
    }

    /**
     * Gets executed applicable checkers.
     */
    public function getApplicableCheckers(): array
    {
        return $this->data['applicableCheckers'];
    }

    /**
     * Gets the total time of all executed actions.
     */
    public function getTotalTime(): float
    {
        if (null === $this->totalTime) {
            $this->totalTime = 0.0;
            foreach ($this->data['actions'] as $action) {
                if (isset($action['time'])) {
                    $this->totalTime += $action['time'];
                }
            }
            foreach ($this->data['applicableCheckers'] as $applicableChecker) {
                if (isset($applicableChecker['time'])) {
                    $this->totalTime += $applicableChecker['time'];
                }
            }
        }

        return $this->totalTime;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return \array_key_exists('name', $this->data)
            ? $this->data['name']
            : $this->logger->getSectionName();
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->data = [
            'actions' => [],
            'applicableCheckers' => []
        ];
    }
}
