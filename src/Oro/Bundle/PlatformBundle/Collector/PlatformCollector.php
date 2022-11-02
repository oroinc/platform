<?php

namespace Oro\Bundle\PlatformBundle\Collector;

use Oro\Bundle\PlatformBundle\Profiler\ProfilerConfig;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Platform data collector. Shows Oro icon in the symfony toolbar with the useful links and data collectors toggle.
 */
class PlatformCollector extends DataCollector
{
    public function setCollectors(array $collectors): void
    {
        $this->data['collectors'] = $collectors;
    }

    public function getCollectors(): array
    {
        return array_diff($this->data['collectors'], ProfilerConfig::ALWAYS_ENABLED_COLLECTORS);
    }

    public function setEnabledCollectors(array $collectors): void
    {
        $this->data['enabledCollectors'] = $collectors;
    }

    public function getEnabledCollectors(): array
    {
        return $this->data['enabledCollectors'];
    }

    public function getTrackContainerChanges(): bool
    {
        return ProfilerConfig::trackContainerChanges();
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $response->headers->setCookie(
            Cookie::create(
                ProfilerConfig::ENABLED_COLLECTORS_COOKIE,
                implode('~', $this->data['enabledCollectors']),
                new \DateTime('next year'),
                httpOnly: false
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'oro_platform';
    }
}
