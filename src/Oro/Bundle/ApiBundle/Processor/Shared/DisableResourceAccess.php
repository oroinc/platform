<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Disables an access to an API resource.
 */
class DisableResourceAccess implements ProcessorInterface
{
    public function __construct(
        private readonly string $message
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        throw new AccessDeniedException($this->message);
    }
}
