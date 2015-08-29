<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\EntityBundle\ORM\OrmConfiguration;

class OrmLogger
{
    /** @var boolean */
    public $enabled = true;

    /** @var array */
    protected $hydrations = [];

    /** @var float */
    protected $startHydration;

    /** @var integer */
    protected $currentHydration = 0;

    /** @var array */
    protected $stats = [];

    /** @var float */
    protected $statsTime = 0;

    /** @var array */
    protected $startStack = [];

    /**
     * @param array           $hydrators
     * @param ManagerRegistry $doctrine
     */
    public function __construct(array $hydrators, ManagerRegistry $doctrine)
    {
        // inject profiling logger and logging hydrators into a configuration of all registered entity managers
        foreach ($doctrine->getManagers() as $manager) {
            if ($manager instanceof EntityManagerInterface) {
                $configuration = $manager->getConfiguration();
                if ($configuration instanceof OrmConfiguration) {
                    $configuration->setAttribute('OrmProfilingLogger', $this);
                    $configuration->setAttribute('LoggingHydrators', $hydrators);
                }
            }
        }
    }

    /**
     * Gets all executed hydrations
     *
     * @return array
     */
    public function getHydrations()
    {
        return $this->hydrations;
    }

    /**
     * Gets statistic of all executed operations
     *
     * @return array
     */
    public function getStats()
    {
        foreach (['persist', 'detach', 'merge', 'remove', 'refresh', 'flush'] as $name) {
            if (!isset($this->stats[$name])) {
                $this->stats[$name] = ['count' => 0, 'time' => 0];
            }
        }

        return $this->stats;
    }

    /**
     * Gets a total time of all executed operations
     *
     * @return float
     */
    public function getStatsTime()
    {
        return $this->statsTime;
    }

    /**
     * Marks a hydration as started
     *
     * @param string $hydrationType
     */
    public function startHydration($hydrationType)
    {
        if ($this->enabled) {
            $this->startHydration = microtime(true);

            $this->hydrations[++$this->currentHydration]['type'] = $hydrationType;
        }
    }

    /**
     * Marks a hydration as stopped
     *
     * @param int   $resultCount
     * @param array $aliasMap
     */
    public function stopHydration($resultCount, $aliasMap)
    {
        if ($this->enabled) {
            $this->hydrations[$this->currentHydration]['time']        = microtime(true) - $this->startHydration;
            $this->hydrations[$this->currentHydration]['resultCount'] = $resultCount;
            $this->hydrations[$this->currentHydration]['aliasMap']    = $aliasMap;
        }
    }

    /**
     * Marks a persist operation as started
     */
    public function startPersist()
    {
        $this->startOperation('persist');
    }

    /**
     * Marks a persist operation as stopped
     */
    public function stopPersist()
    {
        $this->stopOperation('persist');
    }

    /**
     * Marks a detach operation as started
     */
    public function startDetach()
    {
        $this->startOperation('detach');
    }

    /**
     * Marks a detach operation as stopped
     */
    public function stopDetach()
    {
        $this->stopOperation('detach');
    }

    /**
     * Marks a merge operation as started
     */
    public function startMerge()
    {
        $this->startOperation('merge');
    }

    /**
     * Marks a merge operation as stopped
     */
    public function stopMerge()
    {
        $this->stopOperation('merge');
    }

    /**
     * Marks a refresh operation as started
     */
    public function startRefresh()
    {
        $this->startOperation('refresh');
    }

    /**
     * Marks a refresh operation as stopped
     */
    public function stopRefresh()
    {
        $this->stopOperation('refresh');
    }

    /**
     * Marks a remove operation as started
     */
    public function startRemove()
    {
        $this->startOperation('remove');
    }

    /**
     * Marks a remove operation as stopped
     */
    public function stopRemove()
    {
        $this->stopOperation('remove');
    }

    /**
     * Marks a flush operation as started
     */
    public function startFlush()
    {
        $this->startOperation('flush');
    }

    /**
     * Marks a flush operation as stopped
     */
    public function stopFlush()
    {
        $this->stopOperation('flush');
    }

    /**
     * @param string $name
     */
    protected function startOperation($name)
    {
        if ($this->enabled) {
            $this->startStack[$name][] = microtime(true);
        }
    }

    /**
     * @param string $name
     */
    protected function stopOperation($name)
    {
        if ($this->enabled) {
            $time = microtime(true) - array_pop($this->startStack[$name]);
            if (isset($this->stats[$name])) {
                $this->stats[$name]['count'] += 1;
            } else {
                $this->stats[$name] = ['count' => 1, 'time' => 0];
            }
            // add to an execution time only if there are no nested operations
            if (empty($this->startStack[$name])) {
                $this->stats[$name]['time'] += $time;
                // add to a total execution time only if there are no nested operations of any type
                $hasNested = false;
                foreach ($this->startStack as $startStack) {
                    if (!empty($startStack)) {
                        $hasNested = true;
                        break;
                    }
                }
                if (!$hasNested) {
                    $this->statsTime += $time;
                }
            }
        }
    }
}
