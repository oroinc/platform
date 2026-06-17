<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Oro\Bundle\EntityBundle\Twig\Analyzer\EntityVariablesProvider;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

final class EntityVariablesProviderTest extends TestCase
{
    private const CACHE_KEY = 'test_entity_variables';

    private VariablesProvider&MockObject $variablesProvider;

    private CacheInterface&MockObject $cache;

    private EntityVariablesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->variablesProvider = $this->createMock(VariablesProvider::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->provider = new EntityVariablesProvider(
            $this->variablesProvider,
            $this->cache,
            self::CACHE_KEY,
        );
    }

    public function testGetClassVariablesReturnsNullWhenClassIsNotKnown(): void
    {
        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY, self::anything())
            ->willReturnCallback(fn (string $key, callable $cb) => $cb());

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([]);

        self::assertNull($this->provider->getClassVariables('SomeClass'));
    }

    public function testGetClassVariablesReturnsNullRelatedClassWhenDefinitionHasNoRelatedEntityName(): void
    {
        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY, self::anything())
            ->willReturnCallback(fn (string $key, callable $cb) => $cb());

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn(['SomeClass' => ['fullName' => ['type' => 'string', 'label' => 'Full Name']]]);

        self::assertSame(['fullName' => null], $this->provider->getClassVariables('SomeClass'));
    }

    public function testGetClassVariablesReturnsRelatedClassFromDefinitionRelatedEntityName(): void
    {
        $relatedClass = 'Oro\Bundle\AttachmentBundle\Entity\File';

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY, self::anything())
            ->willReturnCallback(fn (string $key, callable $cb) => $cb());

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'SomeClass' => [
                    'orderDefaultPdfFile' => ['type' => 'ref-one', 'related_entity_name' => $relatedClass],
                ],
            ]);

        self::assertSame(['orderDefaultPdfFile' => $relatedClass], $this->provider->getClassVariables('SomeClass'));
    }

    public function testGetClassVariablesAggregatesVariablesFromMultipleClassesIndependently(): void
    {
        $this->cache
            ->expects(self::exactly(2))
            ->method('get')
            ->with(self::CACHE_KEY, self::anything())
            ->willReturnCallback(fn (string $key, callable $cb) => $cb());

        $this->variablesProvider
            ->expects(self::exactly(2))
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'ClassA' => ['propA' => ['type' => 'string']],
                'ClassB' => ['propB' => ['type' => 'ref-one', 'related_entity_name' => 'RelatedClass']],
            ]);

        self::assertSame(['propA' => null], $this->provider->getClassVariables('ClassA'));
        self::assertSame(['propB' => 'RelatedClass'], $this->provider->getClassVariables('ClassB'));
    }

    public function testGetClassVariablesIgnoresNumericVarNames(): void
    {
        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY, self::anything())
            ->willReturnCallback(fn (string $key, callable $cb) => $cb());

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'SomeClass' => [
                    'validProp' => ['type' => 'string'],
                    0 => ['type' => 'ref-one', 'related_entity_name' => 'AnotherClass'],
                ],
            ]);

        self::assertSame(['validProp' => null], $this->provider->getClassVariables('SomeClass'));
    }

    public function testGetClassVariablesReturnsDataFromSharedCacheWithoutCallingProviders(): void
    {
        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY, self::anything())
            ->willReturn(['SomeClass' => ['prop' => 'RelatedClass']]);

        $this->variablesProvider
            ->expects(self::never())
            ->method('getEntityVariableDefinitions');

        self::assertSame(['prop' => 'RelatedClass'], $this->provider->getClassVariables('SomeClass'));
    }

    public function testGetClassVariablesReturnsNullWhenAllVariableNamesAreNumeric(): void
    {
        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY, self::anything())
            ->willReturnCallback(fn (string $key, callable $cb) => $cb());

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'SomeClass' => [
                    0 => ['type' => 'string'],
                    1 => ['type' => 'ref-one', 'related_entity_name' => 'RelatedClass'],
                ],
            ]);

        self::assertNull($this->provider->getClassVariables('SomeClass'));
    }

    public function testGetClassVariablesReturnsNullRelatedClassWhenDefinitionIsScalar(): void
    {
        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY, self::anything())
            ->willReturnCallback(fn (string $key, callable $cb) => $cb());

        $this->variablesProvider
            ->expects(self::once())
            ->method('getEntityVariableDefinitions')
            ->willReturn([
                'SomeClass' => ['varName' => 'scalar-value'],
            ]);

        self::assertSame(['varName' => null], $this->provider->getClassVariables('SomeClass'));
    }

    public function testClearCacheDeletesSharedCache(): void
    {
        $this->cache
            ->expects(self::once())
            ->method('delete')
            ->with(self::CACHE_KEY);

        $this->provider->clearCache();
    }
}
