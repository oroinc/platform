<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Stub;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class FailingResponseListenerStub
{
    public ?int $statusCode = null;

    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->statusCode = $event->getResponse()->getStatusCode();

        throw new \RuntimeException('Listener failed.');
    }
}
