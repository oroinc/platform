<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EmailBundle\Twig\NodeVisitor\SafeGetAttrNodeVisitor;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Extension\AbstractExtension;

/**
 * Registers {@see SafeGetAttrNodeVisitor}.
 */
class SafeGetAttributeNodeExtension extends AbstractExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    #[\Override]
    public function getNodeVisitors(): array
    {
        return [new SafeGetAttrNodeVisitor()];
    }
}
