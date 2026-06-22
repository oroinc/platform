<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\SecurityPolicy;

use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessAnalyzer;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyFilterViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyFunctionViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyMethodViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyPropertyViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyTagViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyViolationInterface;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;

/**
 * Validates a Twig template source against the Twig sandbox security policy.
 *
 * Given a template source and an optional map of variable names to FQCNs,
 * checks for:
 * - Disallowed tags, filters, and functions (compile-time sandbox check).
 *   Only the first such violation per call is reported - Twig throws on the first disallowed element.
 * - Disallowed property and method accesses on the given typed variables
 *   (via static template analysis). All such violations are reported.
 *
 * Returns an empty list when the sandbox extension is not registered in the given Twig environment
 * or when no violations are found.
 *
 * Throws {@see SyntaxError} if the template contains syntax errors.
 */
class TemplateSecurityPolicyChecker
{
    public function __construct(
        private readonly Environment $twigEnvironment,
        private readonly TemplateAccessAnalyzer $templateAccessAnalyzer,
    ) {
    }

    /**
     * Checks the given Twig template source for security policy violations.
     *
     * @param string $templateSource The raw Twig template source code.
     * @param array<string, string> $variableTypes Optional map of variable name to FQCN
     *  (e.g. ['entity' => User::class]). When provided, property and method accesses on the listed variables are
     *  validated against the sandbox policy.
     *
     * @return list<SecurityPolicyViolationInterface>
     *
     * @throws SyntaxError
     * @throws \ReflectionException
     */
    public function checkSecurityPolicy(string $templateSource, array $variableTypes = []): array
    {
        if (!$this->twigEnvironment->hasExtension(SandboxExtension::class)) {
            return [];
        }

        $violations = [];

        $sandboxViolation = $this->getSandboxViolation($templateSource);
        if ($sandboxViolation !== null) {
            $violations[] = $sandboxViolation;
        }

        if ($variableTypes !== []) {
            array_push($violations, ...$this->getAccessViolations($templateSource, $variableTypes));
        }

        return $violations;
    }

    /**
     * Checks the template source for the first disallowed tag, filter, or function.
     * Returns null when no such violation exists.
     *
     * @throws SyntaxError
     */
    private function getSandboxViolation(string $templateSource): ?SecurityPolicyViolationInterface
    {
        try {
            $template = $this->twigEnvironment->createTemplate($templateSource);
            // {@see Twig\Node\CheckSecurityNode}
            $template->unwrap()->checkSecurity();
        } catch (SecurityNotAllowedTagError $e) {
            return new SecurityPolicyTagViolation(
                name: $e->getTagName(),
                templateLine: $e->getTemplateLine(),
                cause: $e
            );
        } catch (SecurityNotAllowedFilterError $e) {
            return new SecurityPolicyFilterViolation(
                name: $e->getFilterName(),
                templateLine: $e->getTemplateLine(),
                cause: $e
            );
        } catch (SecurityNotAllowedFunctionError $e) {
            return new SecurityPolicyFunctionViolation(
                name: $e->getFunctionName(),
                templateLine: $e->getTemplateLine(),
                cause: $e
            );
        }

        return null;
    }

    /**
     * Statically analyzes the template for all property and method accesses on the given typed
     * variables and checks each one against the sandbox security policy.
     *
     * @param string $templateSource
     * @param array<string, string> $variableTypes
     *
     * @return list<SecurityPolicyPropertyViolation|SecurityPolicyMethodViolation>
     *
     * @throws SyntaxError
     * @throws \ReflectionException
     */
    private function getAccessViolations(string $templateSource, array $variableTypes): array
    {
        $accessEntries = $this->templateAccessAnalyzer->analyzeTemplate($templateSource, $variableTypes);

        $sandboxExtension = $this->twigEnvironment->getExtension(SandboxExtension::class);
        $violations = [];

        foreach ($accessEntries as $accessEntry) {
            $instance = (new \ReflectionClass($accessEntry->className))->newInstanceWithoutConstructor();

            if ($accessEntry->accessType === TemplateAccessEntry::ACCESS_TYPE_PROPERTY) {
                try {
                    $sandboxExtension->checkPropertyAllowed($instance, $accessEntry->attributeName);
                } catch (SecurityNotAllowedPropertyError $e) {
                    $violations[] = new SecurityPolicyPropertyViolation(
                        name: $accessEntry->attributeName,
                        variableName: $accessEntry->variableName,
                        entityClass: $accessEntry->className,
                        templateLine: $accessEntry->lineNumber,
                        cause: $e,
                    );
                }
            } elseif ($accessEntry->accessType === TemplateAccessEntry::ACCESS_TYPE_METHOD) {
                try {
                    $sandboxExtension->checkMethodAllowed($instance, $accessEntry->attributeName);
                } catch (SecurityNotAllowedMethodError $e) {
                    $violations[] = new SecurityPolicyMethodViolation(
                        name: $accessEntry->attributeName,
                        variableName: $accessEntry->variableName,
                        entityClass: $accessEntry->className,
                        templateLine: $accessEntry->lineNumber,
                        cause: $e,
                    );
                }
            }
        }

        return $violations;
    }
}
