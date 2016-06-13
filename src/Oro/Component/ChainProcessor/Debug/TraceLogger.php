<?php

namespace Oro\Component\ChainProcessor\Debug;

use Symfony\Component\Stopwatch\Stopwatch;

class TraceLogger
{
    /** @var string */
    protected $sectionName;

    /** @var Stopwatch|null */
    protected $stopwatch;

    /** @var array */
    protected $actions = [];

    /** @var array */
    protected $actionStack = [];

    /** @var array */
    protected $processorStack = [];

    /** @var array */
    protected $unclassifiedProcessors = [];

    /** @var array */
    protected $applicableCheckers = [];

    /** @var string */
    protected $lastApplicableChecker;

    /**
     * @param string         $sectionName
     * @param Stopwatch|null $stopwatch
     */
    public function __construct($sectionName, Stopwatch $stopwatch = null)
    {
        $this->sectionName = $sectionName;
        $this->stopwatch = $stopwatch;
    }

    /**
     * Gets the name of trace section
     *
     * @return string
     */
    public function getSectionName()
    {
        return $this->sectionName;
    }

    /**
     * Gets all executed actions
     *
     * @return array
     */
    public function getActions()
    {
        $actions = $this->actions;
        if (!empty($this->unclassifiedProcessors)) {
            $time = 0;
            foreach ($this->unclassifiedProcessors as $processor) {
                if (array_key_exists('time', $processor)) {
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
     * Gets all executed applicable checkers
     *
     * @return array
     */
    public function getApplicableCheckers()
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
     * Marks an action as started
     *
     * @param string $actionName
     */
    public function startAction($actionName)
    {
        $startStopwatch = $this->stopwatch && empty($this->actionStack);

        $this->actionStack[] = ['name' => $actionName, 'time' => microtime(true), 'subtrahend' => 0];
        if ($startStopwatch) {
            $this->stopwatch->start($this->getStopwatchName(), $this->sectionName);
        }
    }

    /**
     * Marks an action as stopped
     *
     * @param \Exception|null $exception
     */
    public function stopAction(\Exception $exception = null)
    {
        $action = array_pop($this->actionStack);
        $action['time'] = microtime(true) - $action['time'] - $action['subtrahend'];
        if (null !== $exception) {
            $action['exception'] = $exception->getMessage();
        }
        unset($action['subtrahend']);
        $this->addSubtrahend($this->actionStack, $action['time']);
        $this->addSubtrahend($this->processorStack, $action['time']);
        array_unshift($this->actions, $action);
        if (empty($this->actionStack) && $this->stopwatch) {
            $this->stopwatch->stop($this->getStopwatchName());
        }
    }

    /**
     * Marks a processor as started
     *
     * @param string $processorId
     */
    public function startProcessor($processorId)
    {
        $this->processorStack[] = ['id' => $processorId, 'time' => microtime(true), 'subtrahend' => 0];
    }

    /**
     * Marks a processor as stopped
     *
     * @param \Exception|null $exception
     */
    public function stopProcessor(\Exception $exception = null)
    {
        $processor = array_pop($this->processorStack);
        $processor['time'] = microtime(true) - $processor['time'] - $processor['subtrahend'];
        if (null !== $exception) {
            $processor['exception'] = $exception->getMessage();
        }
        unset($processor['subtrahend']);

        if (!empty($this->actionStack)) {
            $action = array_pop($this->actionStack);
            if (!array_key_exists('processors', $action)) {
                $action['processors'] = [];
            }
            array_unshift($action['processors'], $processor);
            $this->actionStack[] = $action;
        } else {
            $this->unclassifiedProcessors[] = $processor;
        }
    }

    /**
     * Marks an applicable checker as started
     *
     * @param string $className The class name of an applicable checker
     */
    public function startApplicableChecker($className)
    {
        if (isset($this->applicableCheckers[$className])) {
            $this->applicableCheckers[$className]['startTime'] = microtime(true);
        } else {
            $this->applicableCheckers[$className] = ['startTime' => microtime(true), 'time' => 0, 'count' => 0];
        }
        $this->lastApplicableChecker = $className;
    }

    /**
     * Marks an applicable checker as stopped
     */
    public function stopApplicableChecker()
    {
        $this->applicableCheckers[$this->lastApplicableChecker]['time'] +=
            microtime(true) - $this->applicableCheckers[$this->lastApplicableChecker]['startTime'];
        $this->applicableCheckers[$this->lastApplicableChecker]['count'] += 1;
    }

    /**
     * @return string
     */
    protected function getStopwatchName()
    {
        return $this->sectionName . '.processors';
    }

    /**
     * @param array  $items
     * @param number $time
     */
    protected function addSubtrahend(array &$items, $time)
    {
        foreach ($items as &$item) {
            $item['subtrahend'] += $time;
        }
    }
}
