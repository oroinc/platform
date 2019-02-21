<?php

namespace Oro\Bundle\PlatformBundle\Collector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Dummy data collector. Required to show Oro icon in the symfony toolbar.
 */
class PlatformCollector extends DataCollector
{
    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'oro_platform';
    }
}
