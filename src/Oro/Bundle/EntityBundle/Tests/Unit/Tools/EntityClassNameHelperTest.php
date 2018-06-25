<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

class EntityClassNameHelperTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ENTITY_ALIAS = 'alias';
    const TEST_ENTITY_PLURAL_ALIAS = 'plural_alias';
    const TEST_ENTITY_CLASS = 'Test\Class';
    const TEST_ENTITY_URL_SAFE_CLASS = 'Test_Class';
    const TEST_CUSTOM_ENTITY_CLASS = 'Extend\Entity\test_entity';
    const TEST_CUSTOM_ENTITY_URL_SAFE_CLASS = 'Extend_Entity_test_entity';

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    protected function setUp()
    {
        $entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityAliasResolver->expects($this->any())
            ->method('getClassByAlias')
            ->willReturnCallback(
                function ($alias) {
                    if (self::TEST_ENTITY_ALIAS === $alias) {
                        return self::TEST_ENTITY_CLASS;
                    }

                    throw new EntityAliasNotFoundException($alias);
                }
            );
        $entityAliasResolver->expects($this->any())
            ->method('getClassByPluralAlias')
            ->willReturnCallback(
                function ($pluralAlias) {
                    if (self::TEST_ENTITY_PLURAL_ALIAS === $pluralAlias) {
                        return self::TEST_ENTITY_CLASS;
                    }

                    throw new EntityAliasNotFoundException($pluralAlias);
                }
            );

        $this->entityClassNameHelper = new EntityClassNameHelper($entityAliasResolver);
    }

    /**
     * @dataProvider resolveEntityClassProvider
     */
    public function testResolveEntityClass($className, $isPluralAlias, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->entityClassNameHelper->resolveEntityClass($className, $isPluralAlias)
        );
    }

    public function resolveEntityClassProvider()
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
    public function testGetUrlSafeClassName($className, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->entityClassNameHelper->getUrlSafeClassName($className)
        );
    }

    public function getUrlSafeClassNameProvider()
    {
        return [
            [self::TEST_ENTITY_CLASS, self::TEST_ENTITY_URL_SAFE_CLASS],
            [self::TEST_ENTITY_URL_SAFE_CLASS, self::TEST_ENTITY_URL_SAFE_CLASS],
            [self::TEST_CUSTOM_ENTITY_CLASS, self::TEST_CUSTOM_ENTITY_URL_SAFE_CLASS],
            [self::TEST_CUSTOM_ENTITY_URL_SAFE_CLASS, self::TEST_CUSTOM_ENTITY_URL_SAFE_CLASS]
        ];
    }
}
