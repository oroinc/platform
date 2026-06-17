<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\Twig\Analyzer\EntityVariablesProvider;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateRendererConfigTypeResolver;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Template;

final class TemplateRendererConfigTypeResolverTest extends TestCase
{
    private EntityVariablesProvider&MockObject $entityVariablesProvider;

    private TemplateRendererConfigTypeResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityVariablesProvider = $this->createMock(EntityVariablesProvider::class);

        $this->resolver = new TemplateRendererConfigTypeResolver($this->entityVariablesProvider);
    }

    public function testResolveReturnsNullImmediatelyForArrayCallType(): void
    {
        $this->entityVariablesProvider
            ->expects(self::never())
            ->method('getClassVariables');

        $result = $this->resolver->resolve('SomeClass', 'attribute', Template::ARRAY_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsNullForMethodCallWhenAttributeDoesNotStartWithGet(): void
    {
        $this->entityVariablesProvider
            ->expects(self::never())
            ->method('getClassVariables');

        $result = $this->resolver->resolve('SomeClass', 'someField', Template::METHOD_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsNullWhenClassIsNotKnownToCache(): void
    {
        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(null);

        $result = $this->resolver->resolve('SomeClass', 'attribute', Template::ANY_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsNullWhenAttributeIsNotAKnownVariableForClass(): void
    {
        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['knownProp' => null]);

        $result = $this->resolver->resolve('SomeClass', 'unknownProp', Template::ANY_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsSkipAccessEntryWhenAttributeIsNamespacePrefixOfDottedVirtualVariable(): void
    {
        // Simulates EntityRouteVariablesProvider providing dotted variables like "url.view", "url.edit".
        // When the template has {{ entity.url.view }}, Twig resolves "url" first. The resolver must
        // return skipAccessEntry=true so AccessNodeVisitor does not record a false positive access entry.
        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['url.view' => null, 'url.edit' => null]);

        $result = $this->resolver->resolve('SomeClass', 'url', Template::ANY_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $result->accessType);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertTrue($result->skipAccessEntry);
        self::assertSame('url', $result->attributeName);
    }

    public function testResolveReturnsNullWhenAttributeIsNotAPrefixOfAnyKnownDottedVariable(): void
    {
        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['url.view' => null, 'url.edit' => null]);

        // "uri" is not an exact match and not a prefix of "url.view" or "url.edit"
        $result = $this->resolver->resolve('SomeClass', 'uri', Template::ANY_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsSkipAccessEntryForMethodCallWhenGetterNameDerivesToDottedVariablePrefix(): void
    {
        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['url.view' => null, 'url.edit' => null]);

        // "getUrl" strips "get" -> "url" which is a namespace prefix of "url.view"
        $result = $this->resolver->resolve('SomeClass', 'getUrl', Template::METHOD_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $result->accessType);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertTrue($result->skipAccessEntry);
        self::assertSame('getUrl', $result->attributeName);
    }

    public function testResolveReturnsPropertyAccessWhenAttributeIsExactDottedVariableNameNotJustPrefix(): void
    {
        // "url.view" as a direct attribute name in classVars (exact match) should not be treated as a prefix.
        // This is an edge case verifying exact-match logic takes precedence.
        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['url.view' => null]);

        $result = $this->resolver->resolve('SomeClass', 'url.view', Template::ANY_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $result->accessType);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertFalse($result->skipAccessEntry);
        self::assertSame('url.view', $result->attributeName);
    }

    public function testResolveReturnsPropertyAccessWithNullResultForAnyCallOnPropertyWithNullRelatedClass(): void
    {
        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['orderDefaultPdfFile' => null]);

        $result = $this->resolver->resolve('SomeClass', 'orderDefaultPdfFile', Template::ANY_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $result->accessType);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertFalse($result->skipAccessEntry);
        self::assertSame('orderDefaultPdfFile', $result->attributeName);
    }

    public function testResolveReturnsPropertyAccessWithRelatedClassForAnyCallOnKnownVariable(): void
    {
        $relatedClass = File::class;

        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['orderDefaultPdfFile' => $relatedClass]);

        $result = $this->resolver->resolve('SomeClass', 'orderDefaultPdfFile', Template::ANY_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $result->accessType);
        self::assertSame($relatedClass, $result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertFalse($result->skipAccessEntry);
        self::assertSame('orderDefaultPdfFile', $result->attributeName);
    }

    public function testResolveReturnsNullForMethodCallWhenDerivedVariableNameNotKnown(): void
    {
        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['otherProp' => null]);

        // 'getMyProp' -> 'myProp' which is not in the cache
        $result = $this->resolver->resolve('SomeClass', 'getMyProp', Template::METHOD_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsMethodAccessWithNullResultForMethodCallOnKnownVariableWithNullRelatedClass(): void
    {
        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['orderDefaultPdfFile' => null]);

        // 'getOrderDefaultPdfFile' -> 'orderDefaultPdfFile' which is in the cache
        $result = $this->resolver->resolve('SomeClass', 'getOrderDefaultPdfFile', Template::METHOD_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $result->accessType);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertFalse($result->skipAccessEntry);
        self::assertSame('getOrderDefaultPdfFile', $result->attributeName);
    }

    public function testResolveReturnsMethodAccessWithRelatedClassForMethodCallOnKnownRelationVariable(): void
    {
        $relatedClass = User::class;

        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with('SomeClass')
            ->willReturn(['user' => $relatedClass]);

        $result = $this->resolver->resolve('SomeClass', 'getUser', Template::METHOD_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $result->accessType);
        self::assertSame($relatedClass, $result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertFalse($result->skipAccessEntry);
        self::assertSame('getUser', $result->attributeName);
    }

    public function testResolveReturnsSkipAccessEntryForMethodCallWhenGetterPrefixMatchesInMixedVariableMap(): void
    {
        $entityClass = 'SomeEntityClass';

        $this->entityVariablesProvider
            ->expects(self::once())
            ->method('getClassVariables')
            ->with($entityClass)
            ->willReturn([
                'url.view' => null,
                'url.edit' => null,
                'name' => null,
            ]);

        // "getUrl" strips "get" -> "url" which is a namespace prefix of "url.view" and "url.edit"
        $result = $this->resolver->resolve($entityClass, 'getUrl', Template::METHOD_CALL);

        self::assertNotNull($result);
        self::assertTrue($result->skipAccessEntry);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
    }
}
