<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EmailBundle\Twig\NodeVisitor\SafeCheckToStringNodeVisitor;
use Oro\Bundle\EmailBundle\Twig\NodeVisitor\SafeGetAttrNodeVisitor;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\AbstractExtension;

/**
 * Registers {@see SafeGetAttrNodeVisitor} and {@see SafeCheckToStringNodeVisitor}.
 *
 * Also carries the event dispatcher {@see \Oro\Bundle\EmailBundle\Twig\Node\SafeGetAttrNode} and
 * {@see \Oro\Bundle\EmailBundle\Twig\Node\SafeCheckToStringNode} use to report a sandbox security policy
 * violation, because a Twig node has no other way to reach a service.
 */
class SafeGetAttributeNodeExtension extends AbstractExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * @bc-layer This accessor is retained for BC reasons. Nothing reads the logger from here anymore: a security
     *           policy violation is logged by
     *           {@see \Oro\Bundle\EmailBundle\EventListener\EmailTemplateSecurityPolicyViolationListener}.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    #[\Override]
    public function getNodeVisitors(): array
    {
        return [new SafeGetAttrNodeVisitor(), new SafeCheckToStringNodeVisitor()];
    }
}
