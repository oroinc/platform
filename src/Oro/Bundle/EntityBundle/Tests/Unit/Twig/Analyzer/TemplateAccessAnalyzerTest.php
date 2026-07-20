<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Oro\Bundle\EntityBundle\Twig\Analyzer\AccessNodeVisitor;
use Oro\Bundle\EntityBundle\Twig\Analyzer\ScopeTracker;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessAnalyzer;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Node\Node;

final class TemplateAccessAnalyzerTest extends TestCase
{
    private AccessNodeVisitor&MockObject $accessNodeVisitor;
    private TemplateAccessAnalyzer $templateAccessAnalyzer;

    #[\Override]
    protected function setUp(): void
    {
        $this->accessNodeVisitor = $this->createMock(AccessNodeVisitor::class);

        $this->templateAccessAnalyzer = new TemplateAccessAnalyzer(
            new Environment(new ArrayLoader()),
            $this->accessNodeVisitor,
        );
    }

    public function testAnalyzeTemplateReturnsEntriesFromAccessNodeVisitor(): void
    {
        $entry1 = new TemplateAccessEntry(
            'Acme\\Entity',
            'entity',
            'name',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            1,
        );
        $entry2 = new TemplateAccessEntry(
            'Acme\\Entity',
            'entity',
            'status',
            TemplateAccessEntry::ACCESS_TYPE_METHOD,
            2,
        );

        $this->accessNodeVisitor
            ->expects(self::once())
            ->method('analyze')
            ->willReturn([$entry1, $entry2]);

        $result = $this->templateAccessAnalyzer->analyzeTemplate(
            '{{ entity.name }}',
            ['entity' => 'Acme\\Entity'],
        );

        self::assertSame([$entry1, $entry2], $result);
    }

    public function testAnalyzeTemplateReturnsEmptyArrayWhenVisitorFindsNoAccesses(): void
    {
        $this->accessNodeVisitor
            ->expects(self::once())
            ->method('analyze')
            ->willReturn([]);

        $result = $this->templateAccessAnalyzer->analyzeTemplate('', []);

        self::assertSame([], $result);
    }

    public function testAnalyzeTemplateInitializesScopeTrackerWithProvidedVariableTypes(): void
    {
        $capturedScopeTracker = null;
        $variableTypes = ['entity' => 'Acme\\Entity', 'user' => 'Acme\\User'];

        $this->accessNodeVisitor
            ->expects(self::once())
            ->method('analyze')
            ->willReturnCallback(
                static function (Node $node, ScopeTracker $scopeTracker) use (&$capturedScopeTracker): array {
                    $capturedScopeTracker = $scopeTracker;

                    return [];
                }
            );

        $this->templateAccessAnalyzer->analyzeTemplate('', $variableTypes);

        self::assertSame('Acme\\Entity', $capturedScopeTracker->getVariableType('entity'));
        self::assertSame('Acme\\User', $capturedScopeTracker->getVariableType('user'));
    }

    public function testAnalyzeTemplateWithEmptyVariableTypesInitializesEmptyScopeTracker(): void
    {
        $capturedScopeTracker = null;

        $this->accessNodeVisitor
            ->expects(self::once())
            ->method('analyze')
            ->willReturnCallback(
                static function (Node $node, ScopeTracker $scopeTracker) use (&$capturedScopeTracker): array {
                    $capturedScopeTracker = $scopeTracker;

                    return [];
                }
            );

        $this->templateAccessAnalyzer->analyzeTemplate('', []);

        self::assertNull($capturedScopeTracker->getVariableType('anyVariable'));
        self::assertFalse($capturedScopeTracker->hasVariable('anyVariable'));
    }

    public function testAnalyzeTemplateThrowsSyntaxErrorForInvalidTemplateSource(): void
    {
        $this->accessNodeVisitor
            ->expects(self::never())
            ->method('analyze');

        $this->expectException(SyntaxError::class);

        $this->templateAccessAnalyzer->analyzeTemplate('{{ unclosed', []);
    }

    public function testAnalyzeTemplateUsesAnalyzedTemplateAsSourceNameInSyntaxError(): void
    {
        try {
            $this->templateAccessAnalyzer->analyzeTemplate('{{ unclosed', []);
            self::fail('Expected SyntaxError was not thrown.');
        } catch (SyntaxError $exception) {
            self::assertSame('analyzed_template', $exception->getSourceContext()->getName());
        }
    }

    public function testAnalyzeTemplateCreatesNewScopeTrackerForEachCall(): void
    {
        $scopeTrackers = [];
        $this->accessNodeVisitor
            ->expects(self::exactly(2))
            ->method('analyze')
            ->willReturnCallback(
                static function (Node $node, ScopeTracker $scopeTracker) use (&$scopeTrackers): array {
                    $scopeTrackers[] = $scopeTracker;
                    return [];
                }
            );

        $this->templateAccessAnalyzer->analyzeTemplate('', ['entity' => 'Acme\\Entity']);
        $this->templateAccessAnalyzer->analyzeTemplate('', ['order' => 'Acme\\Order']);

        self::assertCount(2, $scopeTrackers);
        self::assertNotSame($scopeTrackers[0], $scopeTrackers[1]);
        self::assertSame('Acme\\Entity', $scopeTrackers[0]->getVariableType('entity'));
        self::assertNull($scopeTrackers[0]->getVariableType('order'));
        self::assertSame('Acme\\Order', $scopeTrackers[1]->getVariableType('order'));
        self::assertNull($scopeTrackers[1]->getVariableType('entity'));
    }
}
