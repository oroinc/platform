<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Source;

/**
 * Statically analyzes a Twig template to extract all property and method accesses on typed variables.
 *
 * Given a template source and a variable-to-class-name map, this service parses the template into an AST,
 * traverses it, and returns a flat list of all resolved accesses with their FQCN, attribute name,
 * access type (property vs method), and line number.
 */
class TemplateAccessAnalyzer
{
    public function __construct(
        private readonly Environment $twigEnvironment,
        private readonly AccessNodeVisitor $accessNodeVisitor,
    ) {
    }

    /**
     * Analyzes the given Twig template source and returns all resolved property/method accesses.
     *
     * @param string $templateSource The raw Twig template source code
     * @param array<string, string> $variableTypes Map of variable names to FQCNs
     *
     * @return list<TemplateAccessEntry>
     *
     * @throws SyntaxError If the template contains syntax errors
     */
    public function analyzeTemplate(string $templateSource, array $variableTypes): array
    {
        $source = new Source($templateSource, 'analyzed_template');
        $tokenStream = $this->twigEnvironment->tokenize($source);
        $ast = $this->twigEnvironment->parse($tokenStream);

        $scopeTracker = new ScopeTracker($variableTypes);

        return $this->accessNodeVisitor->analyze($ast, $scopeTracker);
    }
}
