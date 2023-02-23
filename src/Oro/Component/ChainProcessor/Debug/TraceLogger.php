<?php

namespace Oro\Component\ChainProcessor\Debug;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * The logger to log information about execution of chain processor services.
 */
class TraceLogger
{
    private string $sectionName;
    private ?Stopwatch $stopwatch;
    private array $actions = [];
    private array $actionStack = [];
    private int $lastActionIndex = -1;
    private array $processorStack = [];
    private array $unclassifiedProcessors = [];
    private array $applicableCheckers = [];
    private string $lastApplicableChecker;

    public function __construct(string $sectionName, Stopwatch $stopwatch = null)
    {
        $this->sectionName = $sectionName;
        $this->stopwatch = $stopwatch;
    }

    /**
     * Gets the name of trace section.
     */
    public function getSectionName(): string
    {
        return $this->sectionName;
    }

    /**
     * Gets all executed actions.
     */
    public function getActions(): array
    {
        $actions = $this->actions;
        if (!empty($this->unclassifiedProcessors)) {
            $time = 0;
            foreach ($this->unclassifiedProcessors as $processor) {
                if (\array_key_exists('time', $processor)) {
                    $time += $processor['time'];
                }
            }
            $actions[] = [
                'name'       => 'unclassified processors',
                'time'       => $time,
                'processors' => $this->unclassifiedProcessors
            ];
        }

        return $actions;
    }

    /**
     * Gets all executed applicable checkers.
     */
    public function getApplicableCheckers(): array
    {
        $applicableCheckers = [];
        foreach ($this->applicableCheckers as $className => $applicableChecker) {
            $applicableCheckers[] = [
                'class' => $className,
                'time'  => $applicableChecker['time'],
                'count' => $applicableChecker['count']
            ];
        }
        return $applicableCheckers;
    }

    /**
     * Marks an action as started.
     */
    public function startAction(string $actionName): void
    {
        $startStopwatch = $this->stopwatch && empty($this->actionStack);

        $this->actionStack[] = ['name' => $actionName, 'time' => microtime(true), 'subtrahend' => 0];
        $this->lastActionIndex++;
        if ($startStopwatch) {
            $this->stopwatch->start($this->getStopwatchName(), $this->sectionName);
        }
    }

    /**
     * Marks an action as stopped.
     */
    public function stopAction(\Exception $exception = null): void
    {
        $action = array_pop($this->actionStack);
        $this->lastActionIndex--;
        $action['time'] = microtime(true) - $action['time'] - $action['subtrahend'];
        if (null !== $exception) {
            $action['exception'] = $exception->getMessage();
        }
        unset($action['subtrahend']);
        $this->addSubtrahend($this->actionStack, $action['time']);
        $this->addSubtrahend($this->processorStack, $action['time']);
        $this->actions[] = $action;
        if (empty($this->actionStack) && $this->stopwatch) {
            $this->stopwatch->stop($this->getStopwatchName());
        }
    }

    /**
     * Marks a processor as started.
     */
    public function startProcessor(string $processorId): void
    {
        $this->processorStack[] = ['id' => $processorId, 'time' => microtime(true), 'subtrahend' => 0];
    }

    /**
     * Marks a processor as stopped.
     */
    public function stopProcessor(\Exception $exception = null): void
    {
        $processor = array_pop($this->processorStack);
        $processor['time'] = microtime(true) - $processor['time'] - $processor['subtrahend'];
        if (null !== $exception) {
            $processor['exception'] = $exception->getMessage();
        }
        unset($processor['subtrahend']);

        if (!empty($this->actionStack)) {
            if (!\array_key_exists('processors', $this->actionStack[$this->lastActionIndex])) {
                $this->actionStack[$this->lastActionIndex]['processors'] = [$processor];
            } else {
                $this->actionStack[$this->lastActionIndex]['processors'][] = $processor;
            }
        } else {
            $this->unclassifiedProcessors[] = $processor;
        }
    }

    /**
     * Marks an applicable checker as started.
     */
    public function startApplicableChecker(string $className): void
    {
        if (isset($this->applicableCheckers[$className])) {
            $this->applicableCheckers[$className]['startTime'] = microtime(true);
        } else {
            $this->applicableCheckers[$className] = ['startTime' => microtime(true), 'time' => 0, 'count' => 0];
        }
        $this->lastApplicableChecker = $className;
    }

    /**
     * Marks an applicable checker as stopped.
     */
    public function stopApplicableChecker(): void
    {
        $this->applicableCheckers[$this->lastApplicableChecker]['time'] +=
            microtime(true) - $this->applicableCheckers[$this->lastApplicableChecker]['startTime'];
        $this->applicableCheckers[$this->lastApplicableChecker]['count'] += 1;
    }

    private function getStopwatchName(): string
    {
        return $this->sectionName . '.processors';
    }

    private function addSubtrahend(array &$items, float $time): void
    {
        foreach ($items as &$item) {
            $item['subtrahend'] += $time;
        }
    }
}
