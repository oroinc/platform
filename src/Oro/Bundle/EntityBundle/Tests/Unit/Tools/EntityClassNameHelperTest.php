<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

class EntityClassNameHelperTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ENTITY_ALIAS = 'alias';
    private const TEST_ENTITY_PLURAL_ALIAS = 'plural_alias';
    private const TEST_ENTITY_CLASS = 'Test\Class';
    private const TEST_ENTITY_URL_SAFE_CLASS = 'Test_Class';
    private const TEST_CUSTOM_ENTITY_CLASS = 'Extend\Entity\test_entity';
    private const TEST_CUSTOM_ENTITY_URL_SAFE_CLASS = 'Extend_Entity_test_entity';

    /** @var EntityClassNameHelper */
    private $entityClassNameHelper;

    protected function setUp(): void
    {
        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $entityAliasResolver->expects($this->any())
            ->method('getClassByAlias')
            ->willReturnCallback(function ($alias) {
                if (self::TEST_ENTITY_ALIAS === $alias) {
                    return self::TEST_ENTITY_CLASS;
                }

                throw new EntityAliasNotFoundException($alias);
            });
        $entityAliasResolver->expects($this->any())
            ->method('getClassByPluralAlias')
            ->willReturnCallback(function ($pluralAlias) {
                if (self::TEST_ENTITY_PLURAL_ALIAS === $pluralAlias) {
                    return self::TEST_ENTITY_CLASS;
                }

                throw new EntityAliasNotFoundException($pluralAlias);
            });

        $this->entityClassNameHelper = new EntityClassNameHelper($entityAliasResolver);
    }

    /**
     * @dataProvider resolveEntityClassProvider
     */
    public function testResolveEntityClass(?string $className, bool $isPluralAlias, ?string $expected)
    {
        $this->assertEquals(
            $expected,
            $this->entityClassNameHelper->resolveEntityClass($className, $isPluralAlias)
        );
    }

    public function resolveEntityClassProvider(): array
    {
        return [
            [null, false, null],
            ['', false, ''],
            [self::TEST_ENTITY_URL_SAFE_CLASS, false, self::TEST_ENTITY_CLASS],
            [self::TEST_ENTITY_URL_SAFE_CLASS, true, self::TEST_ENTITY_CLASS],
            [self::TEST_ENTITY_CLASS, false, self::TEST_ENTITY_CLASS],
            [self::TEST_ENTITY_CLASS, true, self::TEST_ENTITY_CLASS],
            [self::TEST_CUSTOM_ENTITY_URL_SAFE_CLASS, false, self::TEST_CUSTOM_ENTITY_CLASS],
            [self::TEST_CUSTOM_ENTITY_URL_SAFE_CLASS, true, self::TEST_CUSTOM_ENTITY_CLASS],
            [self::TEST_CUSTOM_ENTITY_CLASS, false, self::TEST_CUSTOM_ENTITY_CLASS],
            [self::TEST_CUSTOM_ENTITY_CLASS, true, self::TEST_CUSTOM_ENTITY_CLASS],
            [self::TEST_ENTITY_ALIAS, false, self::TEST_ENTITY_CLASS],
            [self::TEST_ENTITY_PLURAL_ALIAS, true, self::TEST_ENTITY_CLASS]
        ];
    }

    /**
     * @dataProvider getUrlSafeClassNameProvider
     */
    public function testGetUrlSafeClassName(string $className, string $expected)
    {
        $this->assertEquals(
            $expected,
            $this->entityClassNameHelper->getUrlSafeClassName($className)
        );
    }

    public function getUrlSafeClassNameProvider(): array
    {
        return [
            [self::TEST_ENTITY_CLASS, self::TEST_ENTITY_URL_SAFE_CLASS],
            [self::TEST_ENTITY_URL_SAFE_CLASS, self::TEST_ENTITY_URL_SAFE_CLASS],
            [self::TEST_CUSTOM_ENTITY_CLASS, self::TEST_CUSTOM_ENTITY_URL_SAFE_CLASS],
            [self::TEST_CUSTOM_ENTITY_URL_SAFE_CLASS, self::TEST_CUSTOM_ENTITY_URL_SAFE_CLASS]
        ];
    }
}
