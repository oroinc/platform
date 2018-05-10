<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;

class EnumValueProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var EnumValueProvider
     */
    protected $provider;

    /**
     * @var EnumTranslationCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->cache = $this->createMock(EnumTranslationCache::class);
        $this->provider = new EnumValueProvider($this->doctrineHelper);
    }

    public function testGetEnumChoices()
    {
        $enumClass = '\stdClass';
        $expected = ['id' => 'Name'];
        $this->cache->expects($this->never())
            ->method('fetch')
            ->with($enumClass);

        $this->assertEnumChoices($enumClass);
        $this->assertEquals($expected, $this->provider->getEnumChoices($enumClass));
    }

    public function testGetEnumChoicesWithEmptyCache()
    {
        $enumClass = 'FooBar';
        $expected = ['id' => 'Name'];

        $this->cache->expects($this->once())
            ->method('contains')
            ->with($enumClass)
            ->willReturn(false);
        $this->cache->expects($this->never())
            ->method('fetch');
        $this->cache->expects($this->once())
            ->method('save')
            ->with($enumClass, $expected);
        $this->provider->setEnumTranslationCache($this->cache);

        $this->assertEnumChoices($enumClass);
        $this->assertEquals($expected, $this->provider->getEnumChoices($enumClass));
    }

    public function testGetEnumChoicesFromCache()
    {
        $enumClass = 'FooBar';
        $expected  = [1 => 'Test'];

        $this->cache->expects($this->once())
            ->method('contains')
            ->with($enumClass)
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($enumClass)
            ->willReturn($expected);
        $this->cache->expects($this->never())
            ->method('save');

        $this->provider->setEnumTranslationCache($this->cache);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository')
            ->with($enumClass);

        $this->assertEquals($expected, $this->provider->getEnumChoices($enumClass));
    }

    public function testGetEnumChoicesByCode()
    {
        $code = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $expected = ['id' => 'Name'];

        $this->assertEnumChoices($enumClass);
        $this->assertEquals($expected, $this->provider->getEnumChoicesByCode($code));
    }

    /**
     * @param string $enumClass
     */
    protected function assertEnumChoices($enumClass)
    {
        $enum = $this->createMock(AbstractEnumValue::class);
        $enum->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('id'));
        $enum->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Name'));
        $values = [$enum];

        $repo = $this->createMock(EnumValueRepository::class);
        $repo->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($values));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->will($this->returnValue($repo));
    }

    public function testGetEnumValueByCode()
    {
        $code = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $id = 1;
        $instance = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with($enumClass, $id)
            ->will($this->returnValue($instance));

        $this->assertEquals($instance, $this->provider->getEnumValueByCode($code, $id));
    }

    public function getDefaultEnumValuesByCode()
    {
        $code = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $id = 1;
        $instance = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->will($this->returnValue([$instance]));

        $this->assertEquals([$instance], $this->provider->getEnumValueByCode($code, $id));
    }
}
