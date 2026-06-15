<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\Node;

use Oro\Bundle\EmailBundle\Twig\SafeGetAttributeNodeExtension;
use Oro\Bundle\EntityExtendBundle\Twig\Node\GetAttrNode;
use Twig\Environment;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Source;

/**
 * Extends GetAttrNode for the email sandbox environment.
 *
 * Catches SecurityNotAllowedMethodError and SecurityNotAllowedPropertyError and returns null
 * instead of propagating the exception. Returns false for is-defined tests on denied attributes.
 *
 * Registered exclusively on oro_email.twig.email_environment via SafeGetAttrNodeVisitor.
 */
class SafeGetAttrNode extends GetAttrNode
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    #[\Override]
    public static function attribute(
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
            return parent::attribute(
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
