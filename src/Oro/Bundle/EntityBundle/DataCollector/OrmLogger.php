<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

class OrmLogger
{
    /** @var boolean */
    public $enabled = true;

    /** @var array */
    public $hydrations = [];

    /** @var array */
    public $stats = [
        'persist' => ['count' => 0, 'time' => 0],
        'detach'  => ['count' => 0, 'time' => 0],
        'merge'   => ['count' => 0, 'time' => 0],
        'remove'  => ['count' => 0, 'time' => 0],
        'refresh' => ['count' => 0, 'time' => 0],
        'flush'   => ['count' => 0, 'time' => 0]
    ];

    /** @var float */
    protected $start;

    /** @var integer */
    protected $currentHydration = 0;

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
                if ($configuration instanceof LoggingConfiguration) {
                    $configuration->setOrmProfilingLogger($this);
                    $configuration->setLoggingHydrators($hydrators);
                }
            }
        }
    }

    /**
     * Marks a hydration as started
     *
     * @param string $hydrationType
     */
    public function startHydration($hydrationType)
    {
        if ($this->enabled) {
            $this->start = microtime(true);

            $this->hydrations[++$this->currentHydration]['type'] = $hydrationType;
        }
    }

    /**
     * Marks a hydration as stopped
     *
     * @param int   $resultNum
     * @param array $aliasMap
     */
    public function stopHydration($resultNum, $aliasMap)
    {
        if ($this->enabled) {
            $this->hydrations[$this->currentHydration]['executionMS'] = microtime(true) - $this->start;
            $this->hydrations[$this->currentHydration]['resultNum']   = $resultNum;
            $this->hydrations[$this->currentHydration]['aliasMap']    = $aliasMap;
        }
    }

    /**
     * Marks a persist operation as started
     */
    public function startPersist()
    {
        if ($this->enabled) {
            $this->start = microtime(true);
        }
    }

    /**
     * Marks a persist operation as stopped
     */
    public function stopPersist()
    {
        if ($this->enabled) {
            $this->stats['persist']['count'] += 1;
            $this->stats['persist']['time'] += microtime(true) - $this->start;
        }
    }

    /**
     * Marks a detach operation as started
     */
    public function startDetach()
    {
        if ($this->enabled) {
            $this->start = microtime(true);
        }
    }

    /**
     * Marks a detach operation as stopped
     */
    public function stopDetach()
    {
        if ($this->enabled) {
            $this->stats['detach']['count'] += 1;
            $this->stats['detach']['time'] += microtime(true) - $this->start;
        }
    }

    /**
     * Marks a merge operation as started
     */
    public function startMerge()
    {
        if ($this->enabled) {
            $this->start = microtime(true);
        }
    }

    /**
     * Marks a merge operation as stopped
     */
    public function stopMerge()
    {
        if ($this->enabled) {
            $this->stats['merge']['count'] += 1;
            $this->stats['merge']['time'] += microtime(true) - $this->start;
        }
    }

    /**
     * Marks a refresh operation as started
     */
    public function startRefresh()
    {
        if ($this->enabled) {
            $this->start = microtime(true);
        }
    }

    /**
     * Marks a refresh operation as stopped
     */
    public function stopRefresh()
    {
        if ($this->enabled) {
            $this->stats['refresh']['count'] += 1;
            $this->stats['refresh']['time'] += microtime(true) - $this->start;
        }
    }

    /**
     * Marks a remove operation as started
     */
    public function startRemove()
    {
        if ($this->enabled) {
            $this->start = microtime(true);
        }
    }

    /**
     * Marks a remove operation as stopped
     */
    public function stopRemove()
    {
        if ($this->enabled) {
            $this->stats['remove']['count'] += 1;
            $this->stats['remove']['time'] += microtime(true) - $this->start;
        }
    }

    /**
     * Marks a flush operation as started
     */
    public function startFlush()
    {
        if ($this->enabled) {
            $this->start = microtime(true);
        }
    }

    /**
     * Marks a flush operation as stopped
     */
    public function stopFlush()
    {
        if ($this->enabled) {
            $this->stats['flush']['count'] += 1;
            $this->stats['flush']['time'] += microtime(true) - $this->start;
        }
    }
}
