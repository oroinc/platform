<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Oro\Bundle\EntityBundle\Twig\Analyzer\AccessNodeVisitor;
use Oro\Bundle\EntityBundle\Twig\Analyzer\ResolvedAccess;
use Oro\Bundle\EntityBundle\Twig\Analyzer\ScopeTracker;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TypeResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\ModuleNode;
use Twig\Source;

final class AccessNodeVisitorTest extends TestCase
{
    private TypeResolverInterface&MockObject $typeResolver;
    private AccessNodeVisitor $visitor;
    private Environment $twig;

    #[\Override]
    protected function setUp(): void
    {
        $this->typeResolver = $this->createMock(TypeResolverInterface::class);
        $this->visitor = new AccessNodeVisitor($this->typeResolver);
        $this->twig = new Environment(new ArrayLoader());
    }

    public function testAnalyzeReturnsEmptyArrayForEmptyTemplate(): void
    {
        $this->typeResolver
            ->expects(self::never())
            ->method('resolve');

        $ast = $this->parseTemplate('');
        $scopeTracker = new ScopeTracker([]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertSame([], $accesses);
    }

    public function testAnalyzeReturnsEmptyArrayWhenVariableNotInScope(): void
    {
        $this->typeResolver
            ->expects(self::never())
            ->method('resolve');

        $ast = $this->parseTemplate('{{ unknown.name }}');
        $scopeTracker = new ScopeTracker([]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertSame([], $accesses);
    }

    public function testAnalyzeReturnsEmptyArrayWhenResolverCannotResolveAttribute(): void
    {
        $entityClass = 'Acme\\Entity';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($entityClass, 'name', self::anything())
            ->willReturn(null);

        $ast = $this->parseTemplate('{{ entity.name }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertSame([], $accesses);
    }

    public function testAnalyzeSimplePropertyAccess(): void
    {
        $entityClass = 'Acme\\Entity';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($entityClass, 'name', self::anything())
            ->willReturn(
                new ResolvedAccess(
                    attributeName: 'name',
                    accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                    entityClass: null
                )
            );

        $ast = $this->parseTemplate('{{ entity.name }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(1, $accesses);
        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('name', $accesses[0]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $accesses[0]->accessType);
        self::assertSame(1, $accesses[0]->lineNumber);
    }

    public function testAnalyzeSimpleMethodCall(): void
    {
        $entityClass = 'Acme\\Entity';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($entityClass, 'getName', self::anything())
            ->willReturn(
                new ResolvedAccess(
                    attributeName: 'getName',
                    accessType: TemplateAccessEntry::ACCESS_TYPE_METHOD,
                    entityClass: null
                )
            );

        $ast = $this->parseTemplate('{{ entity.getName() }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(1, $accesses);
        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('getName', $accesses[0]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $accesses[0]->accessType);
        self::assertSame(1, $accesses[0]->lineNumber);
    }

    public function testAnalyzeRecordsCorrectLineNumber(): void
    {
        $entityClass = 'Acme\\Entity';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->willReturn(
                new ResolvedAccess(
                    attributeName: '',
                    accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                    entityClass: null
                )
            );

        // The access is on line 2 due to the leading newline
        $ast = $this->parseTemplate("\n{{ entity.name }}");
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(1, $accesses);
        self::assertSame(2, $accesses[0]->lineNumber);
    }

    public function testAnalyzeMultipleAccessesInTemplate(): void
    {
        $entityClass = 'Acme\\Entity';

        $this->typeResolver
            ->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnCallback(
                static function (string $className, string $attr) use ($entityClass): ?ResolvedAccess {
                    if ($className === $entityClass && $attr === 'name') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }
                    if ($className === $entityClass && $attr === 'status') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }

                    return null;
                }
            );

        $ast = $this->parseTemplate('{{ entity.name }} {{ entity.status }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(2, $accesses);
        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('name', $accesses[0]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $accesses[0]->accessType);
        self::assertSame($entityClass, $accesses[1]->className);
        self::assertSame('entity', $accesses[1]->variableName);
        self::assertSame('status', $accesses[1]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $accesses[1]->accessType);
    }

    public function testAnalyzeDeepChainedPropertyAccess(): void
    {
        $entityClass = 'Acme\\Entity';
        $addressClass = 'Acme\\Address';
        $cityClass = 'Acme\\City';

        $this->typeResolver
            ->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnCallback(
                static function (
                    string $className,
                    string $attr
                ) use (
                    $entityClass,
                    $addressClass,
                    $cityClass
                ): ?ResolvedAccess {
                    if ($className === $entityClass && $attr === 'address') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: $addressClass
                        );
                    }
                    if ($className === $addressClass && $attr === 'city') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: $cityClass
                        );
                    }

                    return null;
                }
            );

        $ast = $this->parseTemplate('{{ entity.address.city }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(2, $accesses);

        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('address', $accesses[0]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $accesses[0]->accessType);

        self::assertSame($addressClass, $accesses[1]->className);
        self::assertSame('address', $accesses[1]->variableName);
        self::assertSame('city', $accesses[1]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $accesses[1]->accessType);
    }

    /**
     * Verifies that {% set alias = entity %} propagates the known type of `entity` to `alias`,
     * so that a subsequent access like {{ alias.name }} can be resolved via the aliased type.
     */
    public function testAnalyzeSetNodePropagatesTypeFromNameExpressionAlias(): void
    {
        $entityClass = 'Acme\\Entity';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($entityClass, 'name', self::anything())
            ->willReturn(
                new ResolvedAccess(
                    attributeName: 'name',
                    accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                    entityClass: null
                )
            );

        $ast = $this->parseTemplate('{% set alias = entity %}{{ alias.name }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(1, $accesses);
        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('alias', $accesses[0]->variableName);
        self::assertSame('name', $accesses[0]->attributeName);
    }

    /**
     * Verifies that when the GetAttrExpression in {% set result = unknown.prop %} cannot be resolved
     * (because `unknown` is not in scope), the resulting variable is not assigned a type, and a
     * subsequent access on that variable also produces no entry.
     */
    public function testAnalyzeSetNodeDoesNotPropagateTypeWhenGetAttrChainIsNotResolvable(): void
    {
        $this->typeResolver
            ->expects(self::never())
            ->method('resolve');

        $ast = $this->parseTemplate('{% set result = unknown.prop %}{{ result.attr }}');
        $scopeTracker = new ScopeTracker([]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertSame([], $accesses);
    }

    /**
     * Verifies that when a SetNode value is a complex expression (neither GetAttrExpression nor
     * NameExpression), the visitor still traverses its children and records any nested accesses.
     * The assigned variable does not receive a type.
     */
    public function testAnalyzeSetNodeVisitsValueForNestedAccessesWhenValueIsComplexExpression(): void
    {
        $entityClass = 'Acme\\Entity';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($entityClass, 'name', self::anything())
            ->willReturn(
                new ResolvedAccess(
                    attributeName: 'name',
                    accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                    entityClass: null
                )
            );

        // entity.name|upper => FilterExpression wrapping a GetAttrExpression;
        // it is not a GetAttrExpression itself, so the else-branch fires in processSetNodeAssignment,
        // visiting the FilterExpression and finding entity.name inside it.
        $ast = $this->parseTemplate('{% set x = entity.name|upper %}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(1, $accesses);
        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('name', $accesses[0]->attributeName);
    }

    /**
     * Verifies that when the for-loop sequence is a NameExpression whose type is already known,
     * the loop variable is assigned that type so body accesses can be resolved.
     */
    public function testAnalyzeForNodeWithNameExpressionSequenceResolvesLoopVariableType(): void
    {
        $itemClass = 'Acme\\Item';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($itemClass, 'name', self::anything())
            ->willReturn(
                new ResolvedAccess(
                    attributeName: 'name',
                    accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                    entityClass: null
                )
            );

        $ast = $this->parseTemplate('{% for item in items %}{{ item.name }}{% endfor %}');
        $scopeTracker = new ScopeTracker(['items' => $itemClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(1, $accesses);
        self::assertSame($itemClass, $accesses[0]->className);
        self::assertSame('item', $accesses[0]->variableName);
        self::assertSame('name', $accesses[0]->attributeName);
    }

    /**
     * Verifies that when the for-loop sequence cannot be resolved to a type (e.g., an inline
     * array literal), the loop variable is not assigned a type and body accesses produce no entries.
     */
    public function testAnalyzeForNodeDoesNotSetLoopVariableTypeWhenSequenceIsNotResolvable(): void
    {
        $this->typeResolver
            ->expects(self::never())
            ->method('resolve');

        $ast = $this->parseTemplate('{% for item in [1, 2, 3] %}{{ item.name }}{% endfor %}');
        $scopeTracker = new ScopeTracker([]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertSame([], $accesses);
    }

    /**
     * Verifies that the else branch of a for-else construct is visited and any accesses inside it
     * are recorded, using variables available in the outer scope.
     */
    public function testAnalyzeForNodeWithElseBranchVisitsElseBody(): void
    {
        $entityClass = 'Acme\\Entity';
        $itemClass = 'Acme\\Item';
        $fallbackClass = 'Acme\\Fallback';

        $this->typeResolver
            ->expects(self::exactly(3))
            ->method('resolve')
            ->willReturnCallback(
                static function (
                    string $className,
                    string $attr
                ) use (
                    $entityClass,
                    $itemClass,
                    $fallbackClass
                ): ?ResolvedAccess {
                    if ($className === $entityClass && $attr === 'items') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: $itemClass,
                            isCollection: true
                        );
                    }
                    if ($className === $itemClass && $attr === 'name') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }
                    if ($className === $fallbackClass && $attr === 'status') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }

                    return null;
                }
            );

        $ast = $this->parseTemplate(
            '{% for item in entity.items %}{{ item.name }}{% else %}{{ fallback.status }}{% endfor %}'
        );
        $scopeTracker = new ScopeTracker(['entity' => $entityClass, 'fallback' => $fallbackClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(3, $accesses);

        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('items', $accesses[0]->attributeName);

        self::assertSame($itemClass, $accesses[1]->className);
        self::assertSame('name', $accesses[1]->attributeName);

        self::assertSame($fallbackClass, $accesses[2]->className);
        self::assertSame('status', $accesses[2]->attributeName);
    }

    /**
     * Verifies that the loop variable introduced by a for-loop is removed from scope after the
     * loop ends, preventing false-positive accesses for any subsequent use of that variable.
     */
    public function testAnalyzeForNodeScopeIsolationPreventsAccessToLoopVariableAfterLoop(): void
    {
        $entityClass = 'Acme\\Entity';
        $itemClass = 'Acme\\Item';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($entityClass, 'items', self::anything())
            ->willReturn(
                new ResolvedAccess(
                    attributeName: 'items',
                    accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                    entityClass: $itemClass,
                    isCollection: true
                )
            );

        // After the loop, `item` is no longer in scope; {{ item.name }} must not produce an entry.
        $ast = $this->parseTemplate('{% for item in entity.items %}{% endfor %}{{ item.name }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(1, $accesses);
        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('items', $accesses[0]->attributeName);
    }

    /**
     * Verifies that nested for-loops correctly push and pop scopes so that each loop variable
     * is accessible only within its own body and resolves to the correct type.
     */
    public function testAnalyzeNestedForLoopsResolveVariablesInCorrectScopes(): void
    {
        $entityClass = 'Acme\\Entity';
        $groupClass = 'Acme\\Group';
        $itemClass = 'Acme\\Item';

        $this->typeResolver
            ->expects(self::exactly(3))
            ->method('resolve')
            ->willReturnCallback(
                static function (
                    string $className,
                    string $attr
                ) use (
                    $entityClass,
                    $groupClass,
                    $itemClass
                ): ?ResolvedAccess {
                    if ($className === $entityClass && $attr === 'groups') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: $groupClass,
                            isCollection: true
                        );
                    }
                    if ($className === $groupClass && $attr === 'items') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: $itemClass,
                            isCollection: true
                        );
                    }
                    if ($className === $itemClass && $attr === 'name') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }

                    return null;
                }
            );

        $ast = $this->parseTemplate(
            '{% for group in entity.groups %}{% for item in group.items %}{{ item.name }}{% endfor %}{% endfor %}'
        );
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(3, $accesses);

        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('groups', $accesses[0]->attributeName);

        self::assertSame($groupClass, $accesses[1]->className);
        self::assertSame('group', $accesses[1]->variableName);
        self::assertSame('items', $accesses[1]->attributeName);

        self::assertSame($itemClass, $accesses[2]->className);
        self::assertSame('item', $accesses[2]->variableName);
        self::assertSame('name', $accesses[2]->attributeName);
    }

    /**
     * Verifies that {% set group = entity.groups[0] %} infers the type of `group`
     * from the collection element type of `entity.groups`, so that a subsequent
     * access like {{ group.name }} can be resolved — matching the type inference
     * that already works in {% for group in entity.groups %}.
     */
    public function testAnalyzeSetNodeInfersTypeFromCollectionArrayElementAccess(): void
    {
        $entityClass = 'Acme\\Entity';
        $groupClass = 'Acme\\Group';

        $this->typeResolver
            ->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnCallback(
                static function (string $className, string $attr) use ($entityClass, $groupClass): ?ResolvedAccess {
                    if ($className === $entityClass && $attr === 'groups') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: $groupClass,
                            isCollection: true
                        );
                    }
                    if ($className === $groupClass && $attr === 'name') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }

                    return null;
                }
            );

        $ast = $this->parseTemplate('{% set group = entity.groups[0] %}{{ group.name }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(2, $accesses);

        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('groups', $accesses[0]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $accesses[0]->accessType);

        self::assertSame($groupClass, $accesses[1]->className);
        self::assertSame('group', $accesses[1]->variableName);
        self::assertSame('name', $accesses[1]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $accesses[1]->accessType);
    }

    /**
     * Verifies that a chained attribute access directly on an array element in an expression,
     * e.g. {{ entity.groups[0].name }}, correctly resolves both accesses in the chain.
     */
    public function testAnalyzeChainedAttributeAccessAfterArrayElementInExpression(): void
    {
        $entityClass = 'Acme\\Entity';
        $groupClass = 'Acme\\Group';

        $this->typeResolver
            ->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnCallback(
                static function (string $className, string $attr) use ($entityClass, $groupClass): ?ResolvedAccess {
                    if ($className === $entityClass && $attr === 'groups') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: $groupClass,
                            isCollection: true
                        );
                    }
                    if ($className === $groupClass && $attr === 'name') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }

                    return null;
                }
            );

        $ast = $this->parseTemplate('{{ entity.groups[0].name }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(2, $accesses);

        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('groups', $accesses[0]->attributeName);

        self::assertSame($groupClass, $accesses[1]->className);
        // For a chained array-element access, the variableName is the integer index cast to string.
        self::assertSame('0', $accesses[1]->variableName);
        self::assertSame('name', $accesses[1]->attributeName);
    }

    /**
     * Verifies that accessing an array element from a variable already in scope works,
     * e.g. {% set group = groups[0] %} where `groups` is a known typed variable.
     * No TypeResolver call should be made for the array access itself.
     */
    public function testAnalyzeSetNodeFromVariableArrayAccessInfersType(): void
    {
        $groupClass = 'Acme\\Group';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($groupClass, 'name', self::anything())
            ->willReturn(
                new ResolvedAccess(
                    attributeName: 'name',
                    accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                    entityClass: null
                )
            );

        $ast = $this->parseTemplate('{% set group = groups[0] %}{{ group.name }}');
        $scopeTracker = new ScopeTracker(['groups' => $groupClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(1, $accesses);
        self::assertSame($groupClass, $accesses[0]->className);
        self::assertSame('group', $accesses[0]->variableName);
        self::assertSame('name', $accesses[0]->attributeName);
    }

    /**
     * Verifies the combined scenario: set from array element, then iterate over a
     * collection on the inferred type.
     * {% set group = entity.groups[0] %}{% for x in group.subGroups %}{{ x.title }}{% endfor %}
     */
    public function testAnalyzeSetFromArrayElementThenForLoopOnInferredType(): void
    {
        $entityClass = 'Acme\\Entity';
        $groupClass = 'Acme\\Group';
        $subGroupClass = 'Acme\\SubGroup';

        $this->typeResolver
            ->expects(self::exactly(3))
            ->method('resolve')
            ->willReturnCallback(
                static function (
                    string $className,
                    string $attr
                ) use (
                    $entityClass,
                    $groupClass,
                    $subGroupClass
                ): ?ResolvedAccess {
                    if ($className === $entityClass && $attr === 'groups') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: $groupClass,
                            isCollection: true
                        );
                    }
                    if ($className === $groupClass && $attr === 'subGroups') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: $subGroupClass,
                            isCollection: true
                        );
                    }
                    if ($className === $subGroupClass && $attr === 'title') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }

                    return null;
                }
            );

        $ast = $this->parseTemplate(
            '{% set group = entity.groups[0] %}{% for x in group.subGroups %}{{ x.title }}{% endfor %}'
        );
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(3, $accesses);

        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('groups', $accesses[0]->attributeName);

        self::assertSame($groupClass, $accesses[1]->className);
        self::assertSame('subGroups', $accesses[1]->attributeName);

        self::assertSame($subGroupClass, $accesses[2]->className);
        self::assertSame('title', $accesses[2]->attributeName);
    }

    /**
     * Verifies that {% set group = entity.getGroups()[0] %} infers the type of `group`
     * from the collection element type returned by the method call, same as the property
     * access variant entity.groups[0] and the for-loop variant.
     */
    public function testAnalyzeSetNodeInfersTypeFromMethodCallCollectionArrayElementAccess(): void
    {
        $entityClass = 'Acme\\Entity';
        $groupClass = 'Acme\\Group';

        $this->typeResolver
            ->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnCallback(
                static function (string $className, string $attr) use ($entityClass, $groupClass): ?ResolvedAccess {
                    if ($className === $entityClass && $attr === 'getGroups') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_METHOD,
                            entityClass: $groupClass,
                            isCollection: true
                        );
                    }
                    if ($className === $groupClass && $attr === 'name') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }

                    return null;
                }
            );

        $ast = $this->parseTemplate('{% set group = entity.getGroups()[0] %}{{ group.name }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(2, $accesses);

        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('getGroups', $accesses[0]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $accesses[0]->accessType);

        self::assertSame($groupClass, $accesses[1]->className);
        self::assertSame('group', $accesses[1]->variableName);
        self::assertSame('name', $accesses[1]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $accesses[1]->accessType);
    }

    /**
     * Verifies that a chained attribute access on method-call array element works inline,
     * e.g. {{ entity.getGroups()[0].name }} resolves both the method call and the attribute.
     */
    public function testAnalyzeChainedAttributeAccessAfterMethodCallArrayElementInExpression(): void
    {
        $entityClass = 'Acme\\Entity';
        $groupClass = 'Acme\\Group';

        $this->typeResolver
            ->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnCallback(
                static function (string $className, string $attr) use ($entityClass, $groupClass): ?ResolvedAccess {
                    if ($className === $entityClass && $attr === 'getGroups') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_METHOD,
                            entityClass: $groupClass,
                            isCollection: true
                        );
                    }
                    if ($className === $groupClass && $attr === 'name') {
                        return new ResolvedAccess(
                            attributeName: $attr,
                            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                            entityClass: null
                        );
                    }

                    return null;
                }
            );

        $ast = $this->parseTemplate('{{ entity.getGroups()[0].name }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertCount(2, $accesses);

        self::assertSame($entityClass, $accesses[0]->className);
        self::assertSame('entity', $accesses[0]->variableName);
        self::assertSame('getGroups', $accesses[0]->attributeName);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $accesses[0]->accessType);

        self::assertSame($groupClass, $accesses[1]->className);
        self::assertSame('name', $accesses[1]->attributeName);
    }

    public function testAnalyzeDoesNotRecordAccessEntryWhenResolvedAccessHasSkipAccessEntryTrue(): void
    {
        // Simulates the case where EntityRouteVariablesProvider provides dotted virtual variables
        // such as "url.view" or "url.edit" for an entity class. The template {{ entity.url.view }}
        // is resolved by Twig as a chain: first entity.url, then (result).view.
        // The resolver returns skipAccessEntry=true for the "url" step to signal that this is a
        // virtual namespace prefix handled by EntityVariablesTemplateProcessor, not a real property.
        // AccessNodeVisitor must NOT record the intermediate "url" step and must NOT continue to "view".
        $entityClass = 'Acme\\Entity';

        $this->typeResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($entityClass, 'url', self::anything())
            ->willReturn(
                new ResolvedAccess(
                    attributeName: 'url',
                    accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
                    entityClass: null,
                    skipAccessEntry: true
                )
            );

        $ast = $this->parseTemplate('{{ entity.url.view }}');
        $scopeTracker = new ScopeTracker(['entity' => $entityClass]);
        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        // Neither "url" nor "view" should appear as recorded access entries.
        self::assertSame([], $accesses);
    }

    public function testAnalyzeForNodeDoesNotSetLoopVariableTypeWhenGetAttrSequenceRootIsNotInScope(): void
    {
        $this->typeResolver
            ->expects(self::never())
            ->method('resolve');

        $ast = $this->parseTemplate('{% for item in unknown.items %}{{ item.name }}{% endfor %}');
        $scopeTracker = new ScopeTracker([]);

        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertSame([], $accesses);
    }

    public function testAnalyzeSetNodeDoesNotPropagateTypeWhenSourceNameExpressionIsUntracked(): void
    {
        $this->typeResolver
            ->expects(self::never())
            ->method('resolve');

        $ast = $this->parseTemplate('{% set alias = unknownVar %}{{ alias.name }}');
        $scopeTracker = new ScopeTracker([]);

        $accesses = $this->visitor->analyze($ast, $scopeTracker);

        self::assertSame([], $accesses);
    }

    private function parseTemplate(string $template): ModuleNode
    {
        $source = new Source($template, 'test.html.twig');
        $tokenStream = $this->twig->tokenize($source);

        return $this->twig->parse($tokenStream);
    }
}
