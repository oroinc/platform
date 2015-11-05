<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Stopwatch\Stopwatch;

use Oro\Bundle\EntityBundle\ORM\OrmConfiguration;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrmLogger
{
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

    /** @var integer */
    protected $hydrationStack = 0;

    /** @var array */
    protected $operationStack = [];

    /** @var array */
    protected $metadataStack = [];

    /** @var Stopwatch|null */
    protected $stopwatch;

    /**
     * @param array           $hydrators
     * @param ManagerRegistry $doctrine
     * @param Stopwatch|null  $stopwatch
     */
    public function __construct(array $hydrators, ManagerRegistry $doctrine, Stopwatch $stopwatch = null)
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

        $this->stopwatch = $stopwatch;
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
        $names = [
            'metadata',
            'getAllMetadata',
            'getMetadataFor',
            'isTransient',
            'persist',
            'detach',
            'merge',
            'remove',
            'refresh',
            'flush'
        ];
        foreach ($names as $name) {
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
        $this->startHydration = microtime(true);

        $this->hydrations[++$this->currentHydration]['type'] = $hydrationType;
        if ($this->stopwatch) {
            $this->stopwatch->start('doctrine.orm.hydrations', 'doctrine');
        }
        $this->hydrationStack++;
    }

    /**
     * Marks a hydration as stopped
     *
     * @param int   $resultCount
     * @param array $aliasMap
     */
    public function stopHydration($resultCount, $aliasMap)
    {
        $this->hydrations[$this->currentHydration]['time']        = microtime(true) - $this->startHydration;
        $this->hydrations[$this->currentHydration]['resultCount'] = $resultCount;
        $this->hydrations[$this->currentHydration]['aliasMap']    = $aliasMap;
        if ($this->stopwatch) {
            $this->stopwatch->stop('doctrine.orm.hydrations');
        }
        $this->hydrationStack--;
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
     * Marks ClassMetadataFactory::getAllMetadata method as started
     */
    public function startGetAllMetadata()
    {
        $this->startMetadata('getAllMetadata');
    }

    /**
     * Marks ClassMetadataFactory::getAllMetadata method as stopped
     */
    public function stopGetAllMetadata()
    {
        $this->stopMetadata('getAllMetadata');
    }

    /**
     * Marks ClassMetadataFactory::getMetadataFor method as started
     */
    public function startGetMetadataFor()
    {
        $this->startMetadata('getMetadataFor');
    }

    /**
     * Marks ClassMetadataFactory::getMetadataFor method as stopped
     */
    public function stopGetMetadataFor()
    {
        $this->stopMetadata('getMetadataFor');
    }

    /**
     * Marks ClassMetadataFactory::isTransient method as started
     */
    public function startIsTransient()
    {
        $this->startMetadata('isTransient');
    }

    /**
     * Marks ClassMetadataFactory::isTransient method as stopped
     */
    public function stopIsTransient()
    {
        $this->stopMetadata('isTransient');
    }

    /**
     * @param string $name
     */
    protected function startOperation($name)
    {
        $startStopwatch = $this->stopwatch && empty($this->operationStack);

        $this->operationStack[$name][] = microtime(true);
        if ($startStopwatch) {
            $this->stopwatch->start('doctrine.orm.operations', 'doctrine');
        }
    }

    /**
     * @param string $name
     */
    protected function stopOperation($name)
    {
        $time = microtime(true) - array_pop($this->operationStack[$name]);
        if (isset($this->stats[$name])) {
            $this->stats[$name]['count'] += 1;
        } else {
            $this->stats[$name] = ['count' => 1, 'time' => 0];
        }
        // add to an execution time only if there are no nested operations
        if (empty($this->operationStack[$name])) {
            unset($this->operationStack[$name]);
            $this->stats[$name]['time'] += $time;
            // add to a total execution time only if there are no nested operations of any type
            if (empty($this->operationStack)) {
                $this->statsTime += $time;
                if ($this->stopwatch) {
                    $this->stopwatch->stop('doctrine.orm.operations');
                }
            }
        }
    }

    /**
     * @param string $name
     */
    protected function startMetadata($name)
    {
        $startStopwatch = $this->stopwatch && empty($this->metadataStack);

        $this->metadataStack[$name][] = microtime(true);
        if ($startStopwatch) {
            $this->stopwatch->start('doctrine.orm.metadata', 'doctrine');
        }
    }

    /**
     * @param string $name
     */
    protected function stopMetadata($name)
    {
        $time = microtime(true) - array_pop($this->metadataStack[$name]);
        if (isset($this->stats[$name])) {
            $this->stats[$name]['count'] += 1;
        } else {
            $this->stats[$name] = ['count' => 1, 'time' => 0];
        }
        if (isset($this->stats['metadata'])) {
            $this->stats['metadata']['count'] += 1;
        } else {
            $this->stats['metadata'] = ['count' => 1, 'time' => 0];
        }
        // add to an execution time only if there are no nested metadata related methods
        if (empty($this->metadataStack[$name])) {
            unset($this->metadataStack[$name]);
            $this->stats[$name]['time'] += $time;
            // add to a total execution time only if it is standalone metadata related method call
            if (empty($this->metadataStack)) {
                $this->stats['metadata']['time'] += $time;
                if (0 === $this->hydrationStack && empty($this->operationStack)) {
                    $this->statsTime += $time;
                }
                if ($this->stopwatch) {
                    $this->stopwatch->stop('doctrine.orm.metadata');
                }
            }
        }
    }
}
