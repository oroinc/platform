<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class OrmDataCollector extends DataCollector
{
    /** @var OrmLogger */
    protected $logger;

    /**
     * @param OrmLogger $logger
     */
    public function __construct(OrmLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['hydrations'] = $this->logger->hydrations;
        $this->data['stats']      = $this->logger->stats;
    }

    public function getHydrations()
    {
        return $this->data['hydrations'];
    }

    public function getHydrationCount()
    {
        return count($this->data['hydrations']);
    }

    public function getHydrationTime()
    {
        $time = 0;
        foreach ($this->data['hydrations'] as $hydration) {
            if (isset($hydration['executionMS'])) {
                $time += $hydration['executionMS'];
            }
        }

        return $time;
    }

    public function getHydratedEntities()
    {
        $result = 0;
        foreach ($this->data['hydrations'] as $hydration) {
            if (isset($hydration['resultNum'])) {
                $result += $hydration['resultNum'];
            }
        }

        return $result;
    }

    public function getStats()
    {
        return $this->data['stats'];
    }

    public function getTotalTime()
    {
        $time = $this->getHydrationTime();
        foreach ($this->data['stats'] as $item) {
            $time += $item['time'];
        }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'orm';
    }
}
