<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\Node;

use Oro\Bundle\EmailBundle\Twig\SafeGetAttributeNodeExtension;
use Twig\Compiler;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Node;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Source;

/**
 * Extends GetAttrExpression for the email sandbox environment.
 *
 * Catches SecurityNotAllowedMethodError and SecurityNotAllowedPropertyError and returns null
 * instead of propagating the exception. Returns false for is-defined tests on denied attributes.
 *
 * Registered exclusively on oro_email.twig.email_environment via SafeGetAttrNodeVisitor.
 */
class SafeGetAttrNode extends GetAttrExpression
{
    /**
     * @inheritdoc
     */
    public function __construct(array $nodes = [], array $attributes = [], int $lineno = 0, string $tag = null)
    {
        // Skip parent::__construct()
        Node::__construct($nodes, $attributes, $lineno, $tag);

        if ($attributes['is_defined_test'] ?? false) {
            $this->enableDefinedTest();
        }
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        parent::compile($compiler);

        $source = $compiler->getSource();
        $source = str_replace('CoreExtension::getAttribute(', self::class . '::safeGetAttribute(', $source);

        $reflection = new \ReflectionProperty($compiler, 'source');
        $reflection->setValue($compiler, $source);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    #[\Override]
    public static function safeGetAttribute(
        Environment $env,
        Source $source,
        $object,
        $item,
        array $arguments = [],
        $type = 'any',
        $isDefinedTest = false,
        $ignoreStrictCheck = false,
        $sandboxed = false,
        int $lineno = -1
    ) {
        try {
            return CoreExtension::getAttribute(
                $env,
                $source,
                $object,
                $item,
                $arguments,
                $type,
                $isDefinedTest,
                $ignoreStrictCheck,
                $sandboxed,
                $lineno
            );
        } catch (SecurityNotAllowedMethodError | SecurityNotAllowedPropertyError $e) {
            if ($isDefinedTest) {
                return false;
            }

            if ($env->hasExtension(SafeGetAttributeNodeExtension::class)) {
                $logger = $env->getExtension(SafeGetAttributeNodeExtension::class)->getLogger();
                $logger->error(
                    'Twig security policy exception caught during email template rendering: ' . $e->getMessage(),
                    ['exception' => $e]
                );
            }

            return null;
        }
    }
}
