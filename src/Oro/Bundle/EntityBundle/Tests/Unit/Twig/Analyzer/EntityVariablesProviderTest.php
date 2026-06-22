<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityBundle\Twig\Analyzer\EntityVariablesProvider;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntityVariablesProviderTest extends TestCase
{
    private const CACHE_KEY = 'test_entity_variables';

    private VariablesProvider&MockObject $variablesProvider;

    private Cache&MockObject $cache;

    private EntityVariablesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->variablesProvider = $this->createMock(VariablesProvider::class);
        $this->cache = $this->createMock(Cache::class);

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
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(false);

        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with(self::CACHE_KEY, []);

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
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(false);

        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with(self::CACHE_KEY, ['SomeClass' => ['fullName' => null]]);

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
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(false);

        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with(self::CACHE_KEY, ['SomeClass' => ['orderDefaultPdfFile' => $relatedClass]]);

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
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(false);

        $this->cache
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::CACHE_KEY, [
                'ClassA' => ['propA' => null],
                'ClassB' => ['propB' => 'RelatedClass'],
            ]);

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
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(false);

        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with(self::CACHE_KEY, ['SomeClass' => ['validProp' => null]]);

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
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(['SomeClass' => ['prop' => 'RelatedClass']]);

        $this->cache
            ->expects(self::never())
            ->method('save');

        $this->variablesProvider
            ->expects(self::never())
            ->method('getEntityVariableDefinitions');

        self::assertSame(['prop' => 'RelatedClass'], $this->provider->getClassVariables('SomeClass'));
    }

    public function testGetClassVariablesReturnsNullWhenAllVariableNamesAreNumeric(): void
    {
        $this->cache
            ->expects(self::once())
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(false);

        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with(self::CACHE_KEY, []);

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
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(false);

        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with(self::CACHE_KEY, ['SomeClass' => ['varName' => null]]);

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
