<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Provider;

use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * Provides the draft session UUID from the request context.
 */
class DraftSessionUuidProvider
{
    public function __construct(
        private readonly RequestContextAwareInterface $router,
        private readonly string $parameterName,
    ) {
    }

    public function getDraftSessionUuid(): ?string
    {
        $uuid = $this->router->getContext()->getParameter($this->parameterName);

        return is_string($uuid) && $uuid !== '' ? $uuid : null;
    }
}
