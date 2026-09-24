<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\Node;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use Oro\Bundle\EmailBundle\Twig\SafeGetAttributeNodeExtension;
use Twig\Compiler;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Node\CheckToStringNode;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Source;
use Twig\Template;

/**
 * Extends CheckToStringNode for the email sandbox environment.
 *
 * Registered exclusively on oro_email.twig.email_environment via SafeCheckToStringNodeVisitor.
 */
class SafeCheckToStringNode extends CheckToStringNode
{
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $expr = $this->getNode('expr');

        $compiler
            ->raw(static::class . '::ensureToStringAllowed($this->env, $this->sandbox, ')
            ->subcompile($expr)
            ->raw(', ')
            ->repr($expr->getTemplateLine())
            ->raw(', $this->source, ')
            ->repr($this->getAttribute('spread'))
            ->raw(', $context)');
    }

    /**
     * Runs the sandbox string-coercion check and turns a denial into a substituted value.
     */
    public static function ensureToStringAllowed(
        Environment $env,
        SandboxExtension $sandbox,
        mixed $object,
        int $lineno,
        Source $source,
        bool $spread = false,
        array $context = []
    ): mixed {
        try {
            return $spread
                ? $sandbox->ensureSpreadAllowed($object, $lineno, $source)
                : $sandbox->ensureToStringAllowed($object, $lineno, $source);
        } catch (SecurityNotAllowedMethodError $error) {
            $event = new EmailTemplateSecurityPolicyViolationEvent(
                violation: $error,
                object: $object,
                item: '__toString',
                arguments: [],
                type: Template::METHOD_CALL,
                isDefinedTest: false,
                source: $source,
                lineno: $lineno,
                context: $context
            );

            if ($env->hasExtension(SafeGetAttributeNodeExtension::class)) {
                $env->getExtension(SafeGetAttributeNodeExtension::class)
                    ->getEventDispatcher()
                    ->dispatch($event);
            }

            $value = $event->getValue();
            if ($spread) {
                return is_iterable($value) ? $value : [];
            }

            return $value;
        }
    }
}
