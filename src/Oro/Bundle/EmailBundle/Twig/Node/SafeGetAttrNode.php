<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\Node;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use Oro\Bundle\EmailBundle\Twig\SafeGetAttributeNodeExtension;
use Oro\Bundle\EntityExtendBundle\Twig\Node\GetAttrNode;
use Twig\Environment;
use Twig\Sandbox\SecurityError;
use Twig\Source;

/**
 * Extends GetAttrNode for the email sandbox environment.
 *
 * An attribute the sandbox denies does not break the email: instead of raising the error, this node dispatches
 * {@see EmailTemplateSecurityPolicyViolationEvent} so that a listener can decide what the attribute resolves to.
 *
 * Registered exclusively on oro_email.twig.email_environment via SafeGetAttrNodeVisitor.
 */
class SafeGetAttrNode extends GetAttrNode
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    #[\Override]
    protected static function onSecurityError(
        SecurityError $error,
        Environment $env,
        Source $source,
        mixed $object,
        mixed $item,
        array $arguments,
        string $type,
        bool $isDefinedTest,
        int $lineno,
        array $context = []
    ): mixed {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: $error,
            object: $object,
            item: $item,
            arguments: $arguments,
            type: $type,
            isDefinedTest: $isDefinedTest,
            source: $source,
            lineno: $lineno,
            context: $context
        );

        if ($env->hasExtension(SafeGetAttributeNodeExtension::class)) {
            $env->getExtension(SafeGetAttributeNodeExtension::class)
                ->getEventDispatcher()
                ->dispatch($event);
        }

        if ($isDefinedTest) {
            return $event->getValue() !== null;
        }

        return $event->getValue();
    }
}
