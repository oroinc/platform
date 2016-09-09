<?php

namespace Oro\Component\ChainProcessor\Debug;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class ActionProcessorDataCollector extends DataCollector
{
    /** @var TraceLogger */
    protected $logger;

    /** @var float */
    protected $totalTime;

    /**
     * @param TraceLogger $logger
     */
    public function __construct(TraceLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['name'] = $this->logger->getSectionName();
        $this->data['actions'] = $this->logger->getActions();
        $this->data['applicableCheckers'] = $this->logger->getApplicableCheckers();
    }

    /**
     * Whether at least one processor was executed.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->data['actions']);
    }

    /**
     * Gets executed actions and processors as a tree.
     *
     * @return array
     */
    public function getActionTree()
    {
        return $this->data['actions'];
    }

    /**
     * Gets executed actions.
     *
     * @return array
     */
    public function getActions()
    {
        $actions = [];
        foreach ($this->data['actions'] as $action) {
            $name = $action['name'];
            $time = isset($action['time'])
                ? $action['time']
                : 0;
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
     *
     * @return int
     */
    public function getActionCount()
    {
        return count($this->data['actions']);
    }

    /**
     * Gets executed processors.
     *
     * @return array
     */
    public function getProcessors()
    {
        $processors = [];
        foreach ($this->data['actions'] as $action) {
            if (isset($action['processors'])) {
                foreach ($action['processors'] as $processor) {
                    $id = $processor['id'];
                    $time = isset($processor['time'])
                        ? $processor['time']
                        : 0;
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
     *
     * @return int
     */
    public function getProcessorCount()
    {
        $count = 0;
        foreach ($this->data['actions'] as $action) {
            if (isset($action['processors'])) {
                $count += count($action['processors']);
            }
        }

        return $count;
    }

    /**
     * Gets executed applicable checkers.
     *
     * @return array
     */
    public function getApplicableCheckers()
    {
        return $this->data['applicableCheckers'];
    }

    /**
     * Gets the total time of all executed actions.
     *
     * @return float
     */
    public function getTotalTime()
    {
        if (null === $this->totalTime) {
            $this->totalTime = 0;
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return array_key_exists('name', $this->data)
            ? $this->data['name']
            : $this->logger->getSectionName();
    }
}
