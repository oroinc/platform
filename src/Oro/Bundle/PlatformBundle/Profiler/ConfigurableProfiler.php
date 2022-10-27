<?php

namespace Oro\Bundle\PlatformBundle\Profiler;

use Oro\Bundle\PlatformBundle\Collector\PlatformCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * Extends Profiler to allow toggling of enabled data collectors
 */
class ConfigurableProfiler extends Profiler
{
    private ?PlatformCollector $platformCollector = null;

    private array $fetchedCollectors;

    /**
     * {@inheritDoc}
     */
    public function add(DataCollectorInterface $collector): void
    {
        if ($collector instanceof PlatformCollector) {
            $this->platformCollector = $collector;
        } else {
            $this->fetchedCollectors[] = $collector->getName();
        }
        if (ProfilerConfig::isCollectorEnabled($collector->getName())) {
            parent::add($collector);
        }
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null): ?Profile
    {
        if ($this->platformCollector) {
            $this->platformCollector->setCollectors($this->fetchedCollectors);
            $this->platformCollector->setEnabledCollectors($this->getEnabledCollectors());
        }

        return parent::collect($request, $response, $exception);
    }

    /**
     * @return string[]
     */
    public function getEnabledCollectors(): array
    {
        return array_keys($this->all());
    }
}
